<?php

namespace App\Models;

use CodeIgniter\Model;

class ClienteModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'clientes';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields = ['nome', 'cpf_cnpj', 'endereco', 'created_at', 'updated_at'];

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

    public function findClient($cpf_cnpj = null, $id = null)
    {
        if (!empty($cpf_cnpj)) {
            $this->where('cpf_cnpj', $cpf_cnpj);
            $query = $this->get();
            if ($query->getNumRows() > 0) {
                return $query->getRow();
            } else {
                return null;
            }
        } else if (!empty($id)) {
            $this->where('id', $id);
            $query = $this->get();
            if ($query->getNumRows() > 0) {
                return $query->getRow();
            } else {
                return null;
            }
        }
    }

    public function createClient($data)
    {
        $clientData = [
            'nome' => $data['nome'],
            'cpf_cnpj' => trim($data['cpf_cnpj']),
            'endereco' => json_encode($data['endereco']),
            'created_at' => date('Y-m-d H:i:s'),
        ];
        $result = $this->insert($clientData);
        if ($result) {
            $newClient = $this->find($result);
            if ($newClient) {
                $response = [
                    'success' => true,
                    'message' => 'Cliente cadastrado com sucesso',
                    'client' => [
                        'id' => $newClient['id'],
                        'nome' => $newClient['nome'],
                        'cpf_cnpj' => $newClient['cpf_cnpj'],
                        'endereco' => json_decode($newClient['endereco']),
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

    public function deleteClient($id)
    {

        if ($this->delete($id)) {
            $response = [
                'success' => true,
                'message' => 'Cliente excluído com sucesso'
            ];
        } else {
            $response = [
                'success' => false,
                'message' => 'Falha ao excluir o cliente'
            ];
        }
        return $response;
    }

    public function updateClient($data, $id)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        if (isset($data['cpf_cnpj']) && !empty($data['cpf_cnpj'])) {
            $cpfExisting = $this->findClient($data['cpf_cnpj']);
            if (!empty($cpfExisting) && $cpfExisting->id !== $id) {
                $response = [
                    'success' => true,
                    'message' => 'O CPF ou CNPJ já possuí registro no nosso banco de dados'
                ];
            }
        }
        if (!isset($response)) {
            if ($this->update($id, $data)) {
                $clientData = $this->findClient("", $id);
                $endereco = $clientData->endereco;
                unset($clientData->endereco);
                $response = [
                    'success' => true,
                    'message' => 'Cliente atualizado com sucesso',
                    'data' => $clientData
                ];
                $response['data']->endereco = json_decode($endereco);
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Falha na atualização do cliente'
                ];
            }
        }
        return $response;
    }

    public function getClients($data, $offset, $limit)
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
                    'cpf_cnpj',
                    'created_at',
                    'updated_at',
                    "endereco.cep",
                    "endereco.logradouro",
                    "endereco.bairro",
                    "endereco.cidade",
                    "endereco.estado",
                    "endereco",
                ];
                break;
            default:
                break;
        }

        $jsonFields = [
            'endereco'
        ];

        $query = $this->builder();

        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $condicao = $value['condicao'] ?? '=';
                $valor = $value['valor'] ?? null;
                $valor2 = $value['valor2'] ?? null;
                if (!empty($valor) && in_array($key, $allowedParameters)) {
                    if ($key === 'created_at' || $key === 'updated_at') {
                        if ($condicao == 'between') {
                            $where = $key . "::date BETWEEN " . "'$valor'" . ' AND ' . "'$valor2'";
                            $query->where($where);
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
                }
                else if(is_array($value) && in_array($key, $allowedParameters)){
                    foreach ($value as $keySecond => $valueSecond) {
                        $keyJoin = "$key.$keySecond";
                        $subCondicao = $valueSecond['condicao'] ?? '=';
                        $subValor = $valueSecond['valor'] ?? null;
                        // $subValor2 = $valueSecond['valor2'] ?? null;
                        
                        if(in_array($keyJoin, $allowedParameters)){
                            if ($subCondicao === 'like') {
                                $where = "$key->>'$keySecond' LIKE '%".$subValor."%'";
                                $query->where($where, null, false);
                            }
                        }
                    }
                }
               
            }
        }

        $query->limit($limit, $offset);
        $results = $query->get()->getResultArray();
        foreach ($results as &$result) {
            foreach ($jsonFields as $value) {
                if(isset($result[$value]) && !empty($result[$value])){
                    $result[$value] = json_decode($result[$value], true);
                }
            }
        }

        return $results;

    }
}
