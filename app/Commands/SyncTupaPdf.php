<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Smalot\PdfParser\Parser;

class SyncTupaPdf extends BaseCommand
{
    protected $group       = 'TUPA';
    protected $name        = 'tupa:sync';
    protected $description = 'Sync database with TUPA PDF';

    public function run(array $params)
    {
        $pdfPath = ROOTPATH . 'public/PROCEDIMIENTOS-ADMINISTRATIVOS-TUPA (1).pdf';
        
        if (!file_exists($pdfPath)) {
            CLI::error("PDF not found at: $pdfPath");
            return;
        }

        CLI::write("Parsing PDF... this may take a moment.", 'yellow');

        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($pdfPath);
            $text = $pdf->getText();
            
            // Normalize spaces but keep newlines for structure
            // $text = preg_replace('/\s+/', ' ', $text); // Don't do this yet, lines are useful
            
            // Split by "Denominación del Procedimiento Administrativo"
            // This keyword seems to start every procedure page/block
            $blocks = preg_split('/Denominación del Procedimiento Administrativo/i', $text);
            
            // First block is intro/index
            array_shift($blocks);
            
            $total = count($blocks);
            CLI::write("Found $total potential procedure blocks.", 'green');
            
            $db = \Config\Database::connect();
            $inserted = 0;
            $updated = 0;
            $skipped = 0;

            foreach ($blocks as $block) {
                $proc = $this->parseBlock($block);
                
                if ($proc) {
                    // Check if exists
                    $exists = $db->table('tramites_tupa')
                                 ->where('codigo', $proc['codigo'])
                                 ->orWhere('nombre_procedimiento', $proc['nombre_procedimiento'])
                                 ->get()
                                 ->getRow();

                    if (!$exists) {
                        $db->table('tramites_tupa')->insert($proc);
                        CLI::write("Inserted: " . substr($proc['nombre_procedimiento'], 0, 50) . "...", 'green');
                        $inserted++;
                    } else {
                        // FORCE UPDATE to fix bad data
                        $db->table('tramites_tupa')->where('id', $exists->id)->update($proc);
                        // CLI::write("Updated: " . substr($proc['nombre_procedimiento'], 0, 50), 'yellow');
                        $updated++;
                    }
                }
            }
            
            CLI::write("Sync Complete. Inserted: $inserted, Skipped: $skipped", 'yellow');

        } catch (\Exception $e) {
            CLI::error("Error: " . $e->getMessage());
        }
    }

    private function parseBlock($text)
    {
        // 1. Extract Name (First non-empty lines)
        $lines = explode("\n", trim($text));
        $name = "";
        $code = "";
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            if (preg_match('/Código:\s*(PA[\w\d]+)/i', $line, $m)) {
                $code = $m[1];
                break;
            }
            
            if (empty($name)) {
                $name = str_replace('"', '', $line); 
            } else {
                $name .= " " . str_replace('"', '', $line);
            }
        }
        
        if (empty($name) || empty($code)) {
            return null;
        }

        // 2. Extract Requirements (Regex based on numbered lists)
        $requisitos = "Ver TUPA (No se detectaron requisitos legibles)";
        // Matches: "1.-", "1. ", "2.-", "2) ", "1.- 1.-"
        if (preg_match_all('/^\s*(?:(?:\d+|[a-z])[\.\-\)]\s*)+(.*)/mi', $text, $matches)) {
            $foundReqs = array_map('trim', $matches[1]);
            // Filter out noise
            $cleanReqs = array_filter($foundReqs, function($r) {
                return strlen($r) > 5 && stripos($r, 'Formato') === false; // "Formato de solicitud" is often noise/header
            });
            if (!empty($cleanReqs)) {
                $requisitos = implode("\n", $cleanReqs);
            }
        }
        
        // If regex failed, check if we captured at least the "Formato de solicitud" line which is common
        if (strpos($requisitos, 'Ver TUPA') !== false && preg_match('/Formato de solicitud/i', $text)) {
             // Fallback: try to capture lines around "Formato"
             // But usually the numbered regex works.
        }

        // 3. Extract Cost (Reliable pattern "Monto - S/")
        $costo = "Gratuito";
        $derecho_pago = "Gratuito";
        if (preg_match('/Monto\s*-\s*(S\/\s*[\d\.]+)/i', $text, $m)) {
            $costo = $m[1];
            $derecho_pago = $m[1];
        } elseif (stripos($text, 'Gratuito') !== false) {
             $costo = "Gratuito";
        }

        // 4. Extract Deadline (Reliable pattern "X días hábiles")
        $plazo = "30 días hábiles"; // Default default
        if (preg_match('/(\d+)\s*días hábiles/i', $text, $m)) {
            $plazo = $m[0];
        }

        // 5. Extract Area
        // Prioritize "Autoridad competente" followed by significant text
        $area = "Mesa de Partes"; // Default
        
        // Try precise match first
        if (preg_match('/Autoridad competente\s+([A-ZÁÉÍÓÚÑ\s\-\.\/]+)(?:\n|$)/u', $text, $m)) {
             $captured = trim($m[1]);
             // Filter out common noise
             if (strlen($captured) > 5 && stripos($captured, 'GERENTE') !== false) {
                 $area = substr($captured, 0, 150);
             }
        }
        
        // Fallback to searching for specific departments if regex failed
        if ($area === "Mesa de Partes") {
             $depts = ['GERENCIA', 'SUB GERENCIA', 'UNIDAD', 'OFICINA', 'ALCALDIA'];
             foreach ($depts as $dept) {
                 if (preg_match('/(' . $dept . ' [A-ZÁÉÍÓÚÑ\s]+)/u', $text, $m)) {
                     $area = substr(trim($m[1]), 0, 150);
                     break;
                 }
             }
        }

        return [
            'codigo' => substr($code, 0, 50),
            'nombre_procedimiento' => $this->cleanName($name),
            'requisitos' => trim($requisitos),
            'derecho_pago' => $derecho_pago,
            'plazo_atencion' => trim($plazo),
            'area' => $area, // extracted or default
            'donde_presentar' => 'Mesa de partes',
            'base_legal' => 'Ver TUPA',
            'categoria' => substr($this->deriveCategory($name), 0, 100),
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
        return 'TRAMITES GENERALES';
    }

    private function generateKeywords($name)
    {
        $words = str_word_count(strtolower($name), 1);
        $stops = ['para', 'del', 'los', 'una', 'las', 'com', 'con', 'por'];
        $keywords = array_diff($words, $stops);
        return implode(' ', array_slice($keywords, 0, 10));
    }
}
