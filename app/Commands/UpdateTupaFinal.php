<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Smalot\PdfParser\Parser;

class UpdateTupaFinal extends BaseCommand
{
    protected $group       = 'TUPA';
    protected $name        = 'tupa:update_final';
    protected $description = 'Sync database with TUPA_FINAL.pdf using high memory limits';

    public function run(array $params)
    {
        // Critical for large PDFs
        ini_set('memory_limit', '3072M'); 
        
        $pdfPath = ROOTPATH . 'public/TUPA_FINAL.pdf';
        
        if (!file_exists($pdfPath)) {
            CLI::error("PDF not found at: $pdfPath");
            return;
        }

        CLI::write("Parsing TUPA_FINAL.pdf... this requires significant memory.", 'yellow');
        
        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($pdfPath);
            $text = $pdf->getText();
            
            // Log raw length for debugging
            CLI::write("Extracted text length: " . strlen($text), 'cyan');

            // Split by "Denominación del Procedimiento" OR "Denominación del Servicio"
            // The PDF uses both headers inconsistently
            $blocks = preg_split('/Denominación del (?:Procedimiento Administrativo|Servicio)/i', $text);
            
            // Remove the first block (usually TOC or temp header)
            array_shift($blocks);
            
            $total = count($blocks);
            CLI::write("Found $total potential procedure blocks.", 'green');
            
            $db = \Config\Database::connect();
            $inserted = 0;
            $updated = 0;
            

            foreach ($blocks as $index => $block) {
                // CLI::showProgress($index + 1, $total);
                if (($index + 1) % 10 === 0) {
                     CLI::write("Processing block " . ($index + 1) . " / $total", 'light_gray');
                }

                try {
                    $proc = $this->parseBlock($block);
                    
                    if ($proc) {
                        $exists = $db->table('tramites_tupa')
                                     ->where('codigo', $proc['codigo'])
                                     ->orWhere('nombre_procedimiento', $proc['nombre_procedimiento'])
                                     ->get()
                                     ->getRow();

                        if (!$exists) {
                            $db->table('tramites_tupa')->insert($proc);
                            $inserted++;
                        } else {
                            $db->table('tramites_tupa')->where('id', $exists->id)->update($proc);
                            $updated++;
                        }
                    }
                } catch (\Exception $e) {
                    CLI::error("Error processing block " . ($index + 1) . ": " . $e->getMessage());
                }
            }
            
            CLI::newLine();
            CLI::write("Sync Complete. Inserted: $inserted, Updated: $updated", 'green');

        } catch (\Exception $e) {
            CLI::error("Error: " . $e->getMessage());
            CLI::error($e->getTraceAsString());
        }
    }

    private function parseBlock($text)
    {
        // 1. Extract Code and Name
        // Usually looks like: "Código: PA123456" then new lines then the name
        $lines = explode("\n", trim($text));
        $lines = array_map('trim', $lines);
        $lines = array_filter($lines); // remove empty
        
        $code = "";
        $name = "";
        
        foreach ($lines as $line) {
            if (empty($code) && preg_match('/Código:?\s*([A-Z0-9\.]+)/i', $line, $m)) {
                $code = $m[1];
                continue;
            }
            // Heuristic: If line is all UPPERCASE and long, it's likely the name
            // Or if it comes before "Requisitos"
            if (empty($name) && !preg_match('/Código:|Requisitos|Descripción/i', $line)) {
                $name = $line;
            } elseif (!empty($name) && !preg_match('/Requisitos|Descripción/i', $line)) {
                 // Append to name if multi-line, assuming it's still part of the title
                 // Stop if we hit keywords
                 $name .= " " . $line;
            }
            if (preg_match('/Requisitos/i', $line)) break;
        }
        
        if (empty($code)) $code = 'S/C-' . md5($name); // Fallback code
        if (empty($name)) return null;

        // 2. Extract Requirements
        // Everything between "Requisitos" and "Derecho de Pago"
        $requisitos = "";
        if (preg_match('/Requisitos.*?:(.*?)(?:Derecho de Pago|Calificación|Plazo)/is', $text, $m)) {
            $requisitos = trim($m[1]);
        }
        

        // Clean up requirements (numbered lists)
        $cleanReqs = [];
        $rawReqs = explode("\n", $requisitos);
        foreach ($rawReqs as $r) {
            $r = trim($r);
            // Keep lines that start with number/dash OR contain "http", "Formulario", "Nota"
            if (preg_match('/^\d+[\.\)]|^\-/i', $r) || preg_match('/http|Formulario|Nota/i', $r)) {
                $cleanReqs[] = $r;
            }
        }
        if (empty($cleanReqs)) $cleanReqs[] = $requisitos; // Fallback to raw block
        $finalReqs = implode("\n", $cleanReqs);

        // Explicitly look for Formularios block if missed
        if (preg_match('/Formularios.*?(http[^\s]+)/is', $text, $fm)) {
             if (strpos($finalReqs, $fm[1]) === false) {
                 $finalReqs .= "\n\nFormulario: " . $fm[1];
             }
        }

        // 3. Extract Cost
        $costo = "Gratuito";
        if (preg_match('/(?:Monto|Derecho de Pago).*?S\/\.?\s*([\d\.]+)/is', $text, $m)) {
            $costo = $m[1];
        }

        // 4. Extract Deadline
        $plazo = "30 días hábiles";
        if (preg_match('/Plazo.*?(\d+)\s*(?:días)/is', $text, $m)) {
            $plazo = $m[1] . " días hábiles";
        }

        // 5. Extract Area (Autoridad)
        $area = "Mesa de Partes";
        if (preg_match('/Autoridad competente.*?:(.*?)(?:\n|$)/is', $text, $m)) {
            $area = trim($m[1]);
        }
        
        return [
            'codigo' => substr($code, 0, 50),
            'nombre_procedimiento' => substr($this->cleanName($name), 0, 500),
            'requisitos' => substr($finalReqs, 0, 3000), // Limit size for DB
            'derecho_pago' => substr($costo, 0, 100),
            'plazo_atencion' => substr($plazo, 0, 100),
            'area' => substr($area, 0, 150),
            'donde_presentar' => 'Mesa de partes',
            'base_legal' => 'Ver TUPA Oficial',
            'categoria' => $this->deriveCategory($name),
            'keywords' => $this->generateKeywords($name),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
    }

    private function cleanName($name)
    {
        return trim(preg_replace('/\s+/', ' ', $name));
    }

    private function deriveCategory($name)
    {
        if (stripos($name, 'matrimonio') !== false) return 'REGISTRO CIVIL';
        if (stripos($name, 'licencia') !== false) return 'LICENCIAS';
        if (stripos($name, 'vehiculo') !== false) return 'TRANSPORTE';
        if (stripos($name, 'edificacion') !== false) return 'OBRAS';
        if (stripos($name, 'constancia') !== false) return 'CERTIFICACIONES';
        return 'TRAMITES GENERALES';
    }

    private function generateKeywords($name)
    {
        $words = str_word_count(strtolower($name), 1);
        $stops = ['para', 'del', 'los', 'una', 'las', 'com', 'con', 'por', 'que', 'en'];
        $keywords = array_diff($words, $stops);
        return implode(' ', array_slice($keywords, 0, 10));
    }
}
