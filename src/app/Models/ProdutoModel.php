<?php

namespace App\Models;

use CodeIgniter\Model;

class ProdutoModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'produtos';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['nome', 'descricao', 'preco', 'quantidade_estoque', 'imagem_url', 'created_at', 'updated_at'];

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

    public function findProduct($id)
    {

        $this->where('id', $id);
        $query = $this->get();
        if ($query->getNumRows() > 0) {
            return $query->getRow();
        } else {
            return null;
        }
    }

    public function createProduct($data)
    {
        $productData = [
            'nome' => $data['nome'],
            'descricao' => isset($data['descricao']) ? trim($data['descricao']) : null,
            'preco' => $data['preco'],
            'quantidade_estoque' => $data['quantidade_estoque'],
            'imagem_url' => $data['imagem_url'],
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $result = $this->insert($productData);
        if ($result) {
            $newProduct = $this->find($result);
            if ($newProduct) {
                $response = [
                    'success' => true,
                    'message' => 'Produto cadastrado com sucesso',
                    'product' => [
                        'id' => $newProduct['id'],
                        'nome' => $newProduct['nome'],
                        'descricao' => $newProduct['descricao'],
                        'preco' => $newProduct['preco'],
                        'quantidade_estoque' => $newProduct['quantidade_estoque'],
                        'imagem_url' => $newProduct['imagem_url'],
                        'created_at' => date('d-m-Y H:i:s', strtotime($newProduct['created_at'])),
                    ]
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Erro ao buscar os dados do produto cadastrado'
                ];
            }
        } else {
            $response = [
                'success' => false,
                'message' => 'Não foi possível cadastrar o produto'
            ];
        }
        return $response;
    }

    public function deleteProduct($id)
    {

        if ($this->delete($id)) {
            $response = [
                'success' => true,
                'message' => 'Produto excluído com sucesso'
            ];
        } else {
            $response = [
                'success' => false,
                'message' => 'Falha ao excluir o produto'
            ];
        }
        return $response;
    }

    public function updateProduct($data, $id)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        if ($this->update($id, $data)) {
            $productData = $this->findProduct($id);
            $response = [
                'success' => true,
                'message' => 'Produto atualizado com sucesso',
                'data' => $productData
            ];
        } else {
            $response = [
                'success' => false,
                'message' => 'Falha na atualização do produto'
            ];
        }
        return $response;
    }

    public function getProducts($data, $offset, $limit)
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
                    'id',
                    'nome',
                    'descricao',
                    'preco',
                    "quantidade_estoque",
                    "created_at",
                    "updated_at",
                ];
                break;
            default:
                break;
        }

        $jsonFields = [];

        $query = $this->builder();

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
                            $where = "$key $condicao '".$valor."'";
                            $query->where($where);
                        }
                    } elseif (is_numeric($valor)) {
                        if ($condicao === 'like') {
                            $query->like($key, '%' . $valor . '%');
                        } else {
                            $where = "$key $condicao '".$valor."'";
                            $query->where($where);
                        }
                    } else {
                        if ($condicao === 'like') {
                            $query->like($key, '%' . $valor . '%');
                        } elseif ($condicao === 'ilike') {
                            $where = "$key ILIKE '%" . $valor . "%'";
                            $query->where($where);
                        } else {
                            $where = "$key $condicao '".$valor."'";
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

        $results = $query->get()->getResultArray();
        foreach ($results as &$result) {
            foreach ($jsonFields as $value) {
                if (isset($result[$value]) && !empty($result[$value])) {
                    $result[$value] = json_decode($result[$value], true);
                }
            }
        }

        return $results;
    }
}
