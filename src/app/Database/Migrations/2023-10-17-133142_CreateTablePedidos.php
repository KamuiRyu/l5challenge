<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTablePedidos extends Migration
{
    public function up()
    {
        $this->db->query("CREATE TYPE status_pedido AS ENUM ('Em aberto', 'Pago', 'Cancelado')");
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
                'default' => 'Em aberto',
                'null' => false
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
