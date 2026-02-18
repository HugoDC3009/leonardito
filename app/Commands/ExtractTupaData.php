<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Smalot\PdfParser\Parser;

class ExtractTupaData extends BaseCommand
{
    protected $group       = 'TUPA';
    protected $name        = 'tupa:extract';
    protected $description = 'Extrae todos los procedimientos del PDF del TUPA y los guarda en la base de datos';

    public function run(array $params)
    {
        CLI::write('Iniciando extracción del TUPA PDF...', 'yellow');

        $pdfPath = FCPATH . 'PROCEDIMIENTOS-ADMINISTRATIVOS-TUPA (1).pdf';
        
        if (!file_exists($pdfPath)) {
            CLI::error('No se encontró el archivo PDF del TUPA en: ' . $pdfPath);
            return;
        }

        try {
            // Parsear el PDF
            $parser = new Parser();
            $pdf = $parser->parseFile($pdfPath);
            $text = $pdf->getText();

            CLI::write('PDF parseado exitosamente. Extrayendo procedimientos...', 'green');

            // Pattern para capturar los procedimientos
            $procedimientos = $this->extractProcedimientos($text);

            if (empty($procedimientos)) {
                CLI::error('No se pudieron extraer procedimientos del PDF');
                return;
            }

            CLI::write('Se encontraron ' . count($procedimientos) . ' procedimientos', 'green');

            // Guardar en base de datos
            $db = \Config\Database::connect();
            $builder = $db->table('tramites_tupa');

            // Limpiar tabla primero
            CLI::write('Limpiando datos anteriores...', 'yellow');
            $builder->truncate();

            $insertados = 0;
            foreach ($procedimientos as $proc) {
                try {
                    $builder->insert($proc);
                    $insertados++;
                } catch (\Exception $e) {
                    CLI::error('Error insertando: ' . $proc['nombre_procedimiento']);
                    CLI::error($e->getMessage());
                }
            }

            CLI::write("✅ Extracción completada: {$insertados} procedimientos insertados", 'green');

        } catch (\Exception $e) {
            CLI::error('Error durante la extracción: ' . $e->getMessage());
        }
    }

    private function extractProcedimientos(string $text): array
    {
        $procedimientos = [];
        
        // Normalizar el texto
        $text = preg_replace('/\s+/', ' ', $text);

        // Patterns comunes para detectar inicio de procedimientos
        // Ajustar según la estructura real del PDF
        $patterns = [
            '/(\d+)\s*[-\.]\s*([A-ZÁÉÍÓÚÑ\s]+?)(?:\s*Requisitos?:?\s*)(.*?)(?:Derecho de pago|Costo|Plazo|Base legal|$)/sim',
            '/PROCEDIMIENTO:\s*([^\n]+)\s*.*?REQUISITOS?:\s*(.*?)(?:DERECHO DE PAGO|PLAZO|BASE LEGAL)/sim',
        ];

        // Intentar extraer con diferentes patrones
        foreach ($patterns as $pattern) {
            preg_match_all($pattern, $text, $matches, PREG_SET_ORDER);
            
            if (!empty($matches)) {
                foreach ($matches as $match) {
                    $nombre = isset($match[2]) ? trim($match[2]) : (isset($match[1]) ? trim($match[1]) : '');
                    $requisitos = isset($match[3]) ? trim($match[3]) : '';
                    
                    if (!empty($nombre) && strlen($nombre) > 5) {
                        $procedimientos[] = [
                            'codigo' => isset($match[1]) && is_numeric($match[1]) ? $match[1] : null,
                            'nombre_procedimiento' => $nombre,
                            'requisitos' => $requisitos ?: 'No especificado',
                            'derecho_pago' => $this->extractDerecho($text, $nombre),
                            'plazo_atencion' => $this->extractPlazo($text, $nombre),
                            'area' => $this->extractArea($text, $nombre),
                            'donde_presentar' => 'Mesa de partes - Municipalidad',
                            'base_legal' => '',
                            'categoria' => $this->determinarCategoria($nombre),
                            'keywords' => $this->generarKeywords($nombre, $requisitos),
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ];
                    }
                }
                break;
            }
        }

        // Si no se encontraron con patterns, hacer extracción simple por líneas
        if (empty($procedimientos)) {
            $procedimientos = $this->extractSimple($text);
        }

        return $procedimientos;
    }

    private function extractDerecho(string $text, string $nombre): string
    {
        $pattern = '/' . preg_quote($nombre, '/') . '.*?(?:Derecho de pago|Costo|Pago):\s*([^\n]+)/si';
        if (preg_match($pattern, $text, $match)) {
            return trim($match[1]);
        }
        return 'Gratuito';
    }

    private function extractPlazo(string $text, string $nombre): string
    {
        $pattern = '/' . preg_quote($nombre, '/') . '.*?Plazo:\s*([^\n]+)/si';
        if (preg_match($pattern, $text, $match)) {
            return trim($match[1]);
        }
        return 'No especificado';
    }

    private function extractArea(string $text, string $nombre): string
    {
        $areas = ['Registro Civil', 'Licencias', 'Catastro', 'Rentas', 'Secretaría General', 'Gerencia Municipal'];
        foreach ($areas as $area) {
            if (stripos($text, $area) !== false) {
                return $area;
            }
        }
        return 'Administración General';
    }

    private function determinarCategoria(string $nombre): string
    {
        $categorias = [
            'matrimonio|nacimiento|defunción' => 'Registro Civil',
            'licencia|funcionamiento' => 'Licencias y Autorizaciones',
            'certificado|constancia' => 'Certificaciones',
            'vehículo|transporte' => 'Transporte',
            'construcción|edificación' => 'Obras',
        ];

        foreach ($categorias as $pattern => $cat) {
            if (preg_match('/' . $pattern . '/i', $nombre)) {
                return $cat;
            }
        }

        return 'Otros Procedimientos';
    }

    private function generarKeywords(string $nombre, string $requisitos): string
    {
        $texto = strtolower($nombre . ' ' . $requisitos);
        
        // Remover palabras comunes
        $stopWords = ['de', 'la', 'el', 'los', 'las', 'para', 'por', 'con', 'en', 'del', 'al', 'un', 'una'];
        $words = explode(' ', $texto);
        $keywords = array_filter($words, function($word) use ($stopWords) {
            return strlen($word) > 3 && !in_array($word, $stopWords);
        });

        return implode(', ', array_unique($keywords));
    }

    private function extractSimple(string $text): array
    {
        $procedimientos = [];
        
        // Lista de procedimientos comunes del TUPA basado en experiencia
        $nombresProcedimientos = [
            'MATRIMONIO CIVIL',
            'CERTIFICADO DE NACIMIENTO',
            'CERTIFICADO DE DEFUNCIÓN',
            'LICENCIA DE FUNCIONAMIENTO',
            'CERTIFICADO DE PARÁMETROS URBANÍSTICOS',
            'DUPLICADO DE TARJETA DE OPERATIVIDAD',
            'SALIDA DE VEHÍCULO DEL DEPÓSITO MUNICIPAL',
        ];

        foreach ($nombresProcedimientos as $nombre) {
            if (stripos($text, $nombre) !== false) {
                $procedimientos[] = [
                    'codigo' => null,
                    'nombre_procedimiento' => $nombre,
                    'requisitos' => 'Consultar en mesa de partes',
                    'derecho_pago' => 'Consultar',
                    'plazo_atencion' => 'Inmediato',
                    'area' => $this->extractArea($text, $nombre),
                    'donde_presentar' => 'Mesa de partes - Municipalidad',
                    'base_legal' => '',
                    'categoria' => $this->determinarCategoria($nombre),
                    'keywords' => $this->generarKeywords($nombre, ''),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
            }
        }

        return $procedimientos;
    }
}
