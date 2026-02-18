<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\TupaModel;

class ConsolidateMarriage extends BaseCommand
{
    protected $group       = 'Custom';
    protected $name        = 'tupa:consolidate-marriage';
    protected $description = 'Unifies all marriage procedures into one.';

    public function run(array $params)
    {
        $model = new TupaModel();
        $results = $model->like('nombre_procedimiento', 'MATRIMONIO CIVIL')->findAll();

        if (empty($results)) {
            CLI::error('No marriage procedures found.');
            return;
        }

        CLI::write('Found ' . count($results) . ' marriage procedures.');

        $baseEntry = null;
        $prices = [];

        foreach ($results as $r) {
            // We'll use the one with the most comprehensive requirements as base, 
            // but in this case they are all same. Let's pick ID 154 (office hours) usually.
            if ($r['id'] == 154 || $baseEntry === null) {
                $baseEntry = $r;
            }
            
            $suffix = str_replace('MATRIMONIO CIVIL', '', $r['nombre_procedimiento']);
            $suffix = trim(str_replace(['(', ')'], '', $suffix));
            if (empty($suffix)) $suffix = "General";
            
            $prices[] = "• **" . $suffix . "**: " . $r['derecho_pago'];
        }

        $newCost = "El costo varía según el lugar y horario:\n\n" . implode("\n", $prices);
        
        // Update the base entry
        $model->update($baseEntry['id'], [
            'nombre_procedimiento' => 'MATRIMONIO CIVIL',
            'derecho_pago' => $newCost
        ]);

        CLI::write('Updated procedure ID ' . $baseEntry['id'] . ' with consolidated costs.', 'green');

        // Delete others
        foreach ($results as $r) {
            if ($r['id'] != $baseEntry['id']) {
                $model->delete($r['id']);
                CLI::write('Deleted redundant procedure ID ' . $r['id'], 'yellow');
            }
        }

        CLI::write('Marriage procedures consolidated successfully!', 'cyan');
    }
}
