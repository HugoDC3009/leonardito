<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\TupaModel;

class FixTupaData extends BaseCommand
{
    protected $group = 'App';
    protected $name = 'app:fix-tupa-data';
    protected $description = 'Corrige y separa procedimientos agrupados en la BD TUPA';

    public function run(array $params)
    {
        $model = new TupaModel();
        
        // 1. FIX MODIFICACIÓN DE PROYECTOS
        CLI::write("=== PROCESANDO MODIFICACIÓN DE PROYECTOS ===", 'yellow');
        $procedimiento = $model->like('nombre_procedimiento', 'MODIFICACIÓN DE PROYECTOS Y/O LICENCIAS DE EDIFICACIÓN')->first();
        
        if ($procedimiento) {
            CLI::write("Encontrado ID: " . $procedimiento['id'], 'green');
            
            // Delete original
            $model->delete($procedimiento['id']);
            CLI::write("Registro original eliminado.", 'red');

            $variants = [
                [
                    'name' => 'MODIFICACIÓN DE PROYECTOS (Antes de emitida la Licencia de Edificación) - 64.1',
                    'price' => 'S/ 153.40'
                ],
                [
                    'name' => 'MODIFICACIÓN DE PROYECTOS (Después de emitida la Licencia de Edificación) - 64.2',
                    'price' => 'S/ 397.00'
                ],
                [
                    'name' => 'MODIFICACIÓN DE PROYECTOS (Para el caso de Proyecto Integral) - 64.3',
                    'price' => 'S/ 566.50'
                ],
                [
                    'name' => 'MODIFICACIÓN DE PROYECTOS (Revisiones Previas / Revisores Urbanos)',
                    'price' => 'S/ 885.90'
                ]
            ];

            foreach ($variants as $v) {
                $data = $procedimiento;
                unset($data['id']); // New ID
                $data['nombre_procedimiento'] = $v['name'];
                $data['derecho_pago'] = $v['price'];
                $data['keywords'] = $procedimiento['keywords'] . ', ' . strtolower($v['name']);
                
                $model->insert($data);
                CLI::write("Insertado: " . $v['name'], 'cyan');
            }
        } else {
            CLI::write("No se encontró el registro base de Modificación de Proyectos (¿Ya procesado?)", 'white');
        }

        // 2. FIX MATRIMONIO CIVIL
        CLI::write("\n=== PROCESANDO MATRIMONIO CIVIL ===", 'yellow');
        $matrimonio = $model->where('nombre_procedimiento', 'MATRIMONIO CIVIL')->first();

        if ($matrimonio) {
            CLI::write("Encontrado ID: " . $matrimonio['id'], 'green');
            
            $model->delete($matrimonio['id']);
            CLI::write("Registro original eliminado.", 'red');

            $variants = [
                ['name' => 'MATRIMONIO CIVIL (En horario de oficina)', 'price' => 'S/ 237.20'],
                ['name' => 'MATRIMONIO CIVIL (Fuera de horario de oficina)', 'price' => 'S/ 262.70'],
                ['name' => 'MATRIMONIO CIVIL (A domicilio)', 'price' => 'S/ 288.00'],
                ['name' => 'MATRIMONIO CIVIL (En local público 4pm-9pm)', 'price' => 'S/ 290.00'],
                ['name' => 'MATRIMONIO CIVIL (Fuera de jurisdicción 9pm-10pm)', 'price' => 'S/ 339.30'],
                ['name' => 'MATRIMONIO CIVIL (Oficiado por el Alcalde)', 'price' => 'S/ 388.00']
            ];

            foreach ($variants as $v) {
                $data = $matrimonio;
                unset($data['id']);
                $data['nombre_procedimiento'] = $v['name'];
                $data['derecho_pago'] = $v['price'];
                $data['keywords'] = $matrimonio['keywords'] . ', ' . strtolower($v['name']);
                
                $model->insert($data);
                CLI::write("Insertado: " . $v['name'], 'cyan');
            }
        } else {
            CLI::write("No se encontró Matrimonio Civil (¿Ya procesado?)", 'white');
        }

        // 3. LICENCIA EDIFICACIÓN (Updates)
        CLI::write("\n=== ACTUALIZANDO LICENCIAS DE EDIFICACIÓN ===", 'yellow');
        
        $updates = [
            'LICENCIA DE EDIFICACIÓN - MODALIDAD "A"' => 'S/ 301.50', // Base default
            'LICENCIA DE EDIFICACIÓN - MODALIDAD B' => 'S/ 559.60',
            'LICENCIA DE EDIFICACIÓN - MODALIDAD C' => 'S/ 1604.60',
            'LICENCIA DE EDIFICACIÓN - MODALIDAD D' => 'S/ 1972.40'
        ];

        foreach ($updates as $term => $price) {
            $rows = $model->like('nombre_procedimiento', $term)->findAll();
            foreach ($rows as $row) {
                // Skip if already specific
                if (strpos($row['derecho_pago'], 'S/') === 0 && $row['derecho_pago'] !== 'S/. 250') continue; 

                $model->update($row['id'], ['derecho_pago' => $price]);
                CLI::write("Actualizado ID " . $row['id'] . ": " . substr($row['nombre_procedimiento'], 0, 50) . "... -> $price", 'cyan');
            }
        }
        
        // Specific Fixes for Modalidad A variants
        $modA_Special = [
            'militar' => 'S/ 327.20',
            'policial' => 'S/ 327.20',
            'cercos' => 'S/ 301.50', // Assuming base
            'demolición' => 'S/ 301.50'
        ];
        
        // Modalidad B Cercos
        $cercosB = $model->like('nombre_procedimiento', 'MODALIDAD B')->like('nombre_procedimiento', 'cercos')->first();
        if ($cercosB) {
            $model->update($cercosB['id'], ['derecho_pago' => 'S/ 557.90']);
            CLI::write("Corrección Modalidad B (Cercos): S/ 557.90", 'green');
        }

        CLI::write("Correcciones finalizadas.", 'green');
    }
}
