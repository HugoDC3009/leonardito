<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTramitesTupaTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'SERIAL',
                'unsigned'       => true,
            ],
            'codigo' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'null'       => true,
            ],
            'nombre_procedimiento' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'requisitos' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'derecho_pago' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'plazo_atencion' => [
                'type'       => 'VARCHAR',
                'constraint' => '200',
                'null'       => true,
            ],
            'area' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
            ],
            'donde_presentar' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'base_legal' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'categoria' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => true,
            ],
            'keywords' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('tramites_tupa', true);

        // Crear índices para búsqueda rápida
        $this->db->query('CREATE INDEX idx_nombre_procedimiento ON tramites_tupa USING gin(to_tsvector(\'spanish\', nombre_procedimiento))');
        $this->db->query('CREATE INDEX idx_keywords ON tramites_tupa USING gin(to_tsvector(\'spanish\', keywords))');
    }

    public function down()
    {
        $this->forge->dropTable('tramites_tupa', true);
    }
}
