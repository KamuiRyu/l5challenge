<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableClientes extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 5,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'nome' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
            ],
            'cpf_cnpj' => [
                'type' => 'VARCHAR',
                'constraint' => 14,
            ],
            'endereco' => [
                'type' => 'json',
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'useCurrent' => true,
            ],
            'updated_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('clientes');
    }

    public function down()
    {
        $this->forge->dropTable('clientes');
    }
}
