<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTablePedidos extends Migration
{
    public function up()
    {
        $this->db->query("CREATE TYPE status_pedido AS ENUM ('em aberto', 'pago', 'cancelado')");
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'auto_increment' => TRUE
            ],
            'produto_id' => [
                'type' => 'INT',
            ],
            'cliente_id' => [
                'type' => 'INT',
            ],
            'preco' => [
                'type' => 'DECIMAL(10, 2)',
            ],
            'quantidade' => [
                'type' => 'INT',
            ],
            'data_pedido' => [
                'type' => 'TIMESTAMP',
            ],
            'status' => [
                'type' => 'status_pedido',
                'default' => 'em aberto',
                'null' => false
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => true
            ],
            'updated_at' => [
                'type' => 'TIMESTAMP',
                'null' => true
            ],
        ]);

        $this->forge->addKey('id', TRUE);
        $this->forge->addField('FOREIGN KEY (cliente_id) REFERENCES clientes(id)');
        $this->forge->addField('FOREIGN KEY (produto_id) REFERENCES produtos(id)');

        $this->forge->createTable('pedidos');
    }

    public function down()
    {
        $this->db->query("DROP TYPE IF EXISTS status_pedido");
        $this->forge->dropTable('pedidos');
    }
}
