<?php

namespace App\Models;

use CodeIgniter\Model;

class OrderModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'pedidos';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['produto_id', 'cliente_id', 'preco', 'quantidade', 'data_pedido', 'status', 'created_at', 'updated_at'];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];


    public function findOrder($id)
    {

        $this->where('id', $id);
        $query = $this->get();
        if ($query->getNumRows() > 0) {
            return $query->getRow();
        } else {
            return null;
        }
    }

    public function createOrder($data)
    {

        $orderData = [
            'preco' => $data['preco'],
            'quantidade' => trim($data['quantidade']),
            'data_pedido' => trim($data['data_pedido']),
            'status' => trim(strtolower($data['status'])),
            'produto_id' => trim($data['produto']['id']),
            'cliente_id' => trim($data['cliente']['id']),
            'created_at' => trim(date('Y-m-d H:i:s')),
        ];
        $result = $this->insert($orderData);
        if ($result) {
            $this->select('p.id, p.preco, p.quantidade, p.data_pedido, p.status, p.created_at, p.updated_at, pr.nome AS produto_nome, pr.preco AS produto_preco, pr.quantidade_estoque AS produto_estoque, pr.imagem_url AS produto_imagem, c.nome AS cliente_nome, c.cpf_cnpj AS cliente_cpf_cnpj, c.endereco AS cliente_endereco');
            $this->from('pedidos AS p');
            $this->join('produtos AS pr', 'p.produto_id = pr.id', 'inner');
            $this->join('clientes AS c', 'p.cliente_id = c.id', 'inner');
            $this->where("p.id = $result");
            $newOrder = $this->get()->getRowArray();
            if ($newOrder) {
                $response = [
                    'success' => true,
                    'message' => 'Cliente cadastrado com sucesso',
                    'pedido' => [
                        'id' => $newOrder['id'],
                        'nome' => $newOrder['preco'],
                        'cpf_cnpj' => $newOrder['data_pedido'],
                        'endereco' => $newOrder['status'],
                        'created_at' => $newOrder['created_at'],
                        'produto' => [
                            'nome' => $newOrder['produto_nome'],
                            'preco' => $newOrder['produto_preco'],
                            'estoque' => $newOrder['produto_estoque'],
                            'imagem' => $newOrder['produto_imagem'],
                        ],
                        'cliente' => [
                            'nome' => $newOrder['cliente_nome'],
                            'cpf_cnpj' => $newOrder['cliente_cpf_cnpj'],
                            'endereco' => json_decode($newOrder['cliente_endereco']),
                        ]
                    ]
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Erro ao buscar os dados do cliente cadastrado'
                ];
            }
        } else {
            $response = [
                'success' => false,
                'message' => 'Não foi possível cadastrar o cliente'
            ];
        }
        return $response;
    }

    public function deleteOrder($id)
    {
        if ($this->delete($id)) {
            $response = [
                'success' => true,
                'message' => 'Pedido excluído com sucesso'
            ];
        } else {
            $response = [
                'success' => false,
                'message' => 'Falha ao excluir o pedido'
            ];
        }
        return $response;
    }

    public function updateOrder($data, $id)
    {
        if (isset($data['produto']['id']) && !empty($data['produto']['id'])) {
            $data['produto_id'] = $data['produto']['id'];
            unset($data['produto']['id']);
        }

        if (isset($data['cliente']['id']) && !empty($data['cliente']['id'])) {
            $data['cliente_id'] = $data['cliente']['id'];
            unset($data['cliente']['id']);
        }
        $data['updated_at'] = date('Y-m-d H:i:s');
        if ($this->update($id, $data)) {
            $this->select('p.id, p.preco, p.quantidade, p.data_pedido, p.status, p.created_at, p.updated_at, pr.nome AS produto_nome, pr.preco AS produto_preco, pr.quantidade_estoque AS produto_estoque, pr.imagem_url AS produto_imagem, c.nome AS cliente_nome, c.cpf_cnpj AS cliente_cpf_cnpj, c.endereco AS cliente_endereco');
            $this->from('pedidos AS p');
            $this->join('produtos AS pr', 'p.produto_id = pr.id', 'inner');
            $this->join('clientes AS c', 'p.cliente_id = c.id', 'inner');
            $this->where("p.id = $id");
            $newOrder = $this->get()->getRowArray();
            $response = [
                'success' => true,
                'message' => 'Pedido atualizado com sucesso',
                'data' => [
                    'id' => $newOrder['id'],
                    'nome' => $newOrder['preco'],
                    'cpf_cnpj' => $newOrder['data_pedido'],
                    'endereco' => $newOrder['status'],
                    'created_at' => $newOrder['created_at'],
                    'produto' => [
                        'nome' => $newOrder['produto_nome'],
                        'preco' => $newOrder['produto_preco'],
                        'estoque' => $newOrder['produto_estoque'],
                        'imagem' => $newOrder['produto_imagem'],
                    ],
                    'cliente' => [
                        'nome' => $newOrder['cliente_nome'],
                        'cpf_cnpj' => $newOrder['cliente_cpf_cnpj'],
                        'endereco' => json_decode($newOrder['cliente_endereco']),
                    ]
                ]
            ];
        } else {
            $response = [
                'success' => false,
                'message' => 'Falha na atualização do pedido'
            ];
        }
        return $response;
    }


    public function getOrders($data, $offset, $limit)
    {
        $query = $this->filters($data, $offset, $limit, 1);
        if (!empty($query)) {
            return ['success' => true, 'data' => $query];
        } else {
            return ['success' => true, 'message' => 'Nenhum registro encontrado'];
        }
    }

    public function filters($data, $offset, $limit, $type)
    {
        switch ($type) {
            case 1:
                $allowedParameters = [
                    'preco',
                    'quantidade',
                    'data_pedido',
                    'status',
                    "created_at",
                    "updated_at",
                    "produto_id",
                    "cliente_id"
                ];
                break;
            default:
                break;
        }

        $innerJoin = ['produtos' => 'produto_id', 'clientes' => 'cliente_id'];

        $tableMain = 'pedidos';

        $produtoColumns = ['nome', 'descricao', 'preco', 'quantidade_estoque', 'imagem_url', 'created_at', 'updated_at'];
        $clienteColumns = ['nome', 'cpf_cnpj', 'endereco', 'created_at', 'updated_at'];

        $query = $this->builder();
        $query = $query->select("$tableMain.*");

        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $condicao = $value['condicao'] ?? '=';
                $valor = $value['valor'] ?? null;
                $valor2 = $value['valor2'] ?? null;
                if (!empty($valor) && in_array($key, $allowedParameters)) {
                    if ($key === 'created_at' || $key === 'updated_at') {
                        if ($condicao == 'between') {
                            $where = "\"$key\"::date BETWEEN '$valor' AND '$valor2'";
                            $query->where($where, null, false);
                        } else {
                            $where = "$key $condicao '" . $valor . "'";
                            $query->where($where);
                        }
                    } elseif (is_numeric($valor)) {
                        if ($condicao === 'like') {
                            $query->like($key, '%' . $valor . '%');
                        } else {
                            $where = "$key $condicao '" . $valor . "'";
                            $query->where($where);
                        }
                    } else {
                        if ($condicao === 'like') {
                            $query->like($key, '%' . $valor . '%');
                        } elseif ($condicao === 'ilike') {
                            $where = "$key ILIKE '%" . $valor . "%'";
                            $query->where($where);
                        } else {
                            $where = "$key $condicao '" . $valor . "'";
                            $query->where($where);
                        }
                    }
                } else if (is_array($value) && in_array($key, $allowedParameters)) {
                    foreach ($value as $keySecond => $valueSecond) {
                        $keyJoin = "$key.$keySecond";
                        $subCondicao = $valueSecond['condicao'] ?? '=';
                        $subValor = $valueSecond['valor'] ?? null;
                        // $subValor2 = $valueSecond['valor2'] ?? null;

                        if (in_array($keyJoin, $allowedParameters)) {
                            if ($subCondicao === 'like') {
                                $where = "$key->>'$keySecond' LIKE '%" . $subValor . "%'";
                                $query->where($where, null, false);
                            }
                        }
                    }
                }
            }
        }

        if (isset($innerJoin)) {
            foreach ($innerJoin as $keyJoin => $join) {
                $tableAlias = "$keyJoin";
                $query->join("$keyJoin AS $tableAlias", "$tableAlias.id = $tableMain.$join", 'inner');
                $columns = null;

                if ($keyJoin === 'produtos') {
                    $columns = $produtoColumns;
                } elseif ($keyJoin === 'clientes') {
                    $columns = $clienteColumns;
                }
                if (!empty($columns)) {
                    foreach ($columns as $column) {;
                        $query->select("$tableAlias.$column AS {$tableAlias}_$column");
                    }
                }
            }
        }



        $query->limit($limit, $offset);
        $sql = $query->getCompiledSelect();
        $newQuery = $this->query($sql);
        $results = $newQuery->getResultArray();;
        $organizedResults = [];
        $cont = 0;
        foreach ($results as $result) {

            if (!isset($organizedResults[$cont])) {
                $organizedResults[$cont] = [
                    'pedido' => $result,
                    'cliente' => [],
                    'produto' => []
                ];
            }

            foreach ($result as $columnName => $columnValue) {
                if (strpos($columnName, '_') !== false) {
                    list($tableName, $newColumnName) = explode('_', $columnName, 2);
                    if ($tableName === 'clientes') {
                        $organizedResults[$cont]['cliente'][$newColumnName] = json_decode($columnValue, true) !== null ? json_decode($columnValue, true) : $columnValue;
                        unset($organizedResults[$cont]['pedido'][$columnName]);
                    } elseif ($tableName === 'produtos') {
                        $organizedResults[$cont]['produto'][$newColumnName] = $columnValue;
                        unset($organizedResults[$cont]['pedido'][$columnName]);
                    }
                }
            }

            $cont++;
        }

        return $organizedResults;
    }
}
