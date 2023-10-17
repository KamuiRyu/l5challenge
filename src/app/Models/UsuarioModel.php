<?php

namespace App\Models;

use CodeIgniter\Model;

class UsuarioModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'usuarios';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['email', 'senha', 'created_at', 'updated_at'];

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

    public function findUser($id, $email)
    {
        if(!empty($id)){
            $this->where('id', $id);
            $query = $this->get();
            if ($query->getNumRows() > 0) {
                return $query->getRow();
            } else {
                return null;
            }
        }else if(!empty($email)){
            $this->where('email', $email);
            $query = $this->get();
            if ($query->getNumRows() > 0) {
                return $query->getRow();
            } else {
                return null;
            }
        }
        return null;
        
    }

    public function create_user($data)
    {
        $userData = [
            'email' => $data['email'],
            'senha' => password_hash($data['senha'], PASSWORD_DEFAULT),
            'created_at' => date('Y-m-d H:i:s'),
        ];

        

        $result = $this->insert($userData);
        if ($result) {
            $newUser = $this->find($result);
            if ($newUser) {
                $response = [
                    'success' => true,
                    'message' => 'Usuário cadastrado com sucesso',
                    'user' => [
                        'id' => $newUser['id'],
                        'email' => $newUser['email'],
                        'created_at' => date('d-m-Y H:i:s', strtotime($newUser['created_at'])),
                    ]
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Erro ao buscar os dados do usuário cadastrado'
                ];
            }
        } else {
            $response = [
                'success' => false,
                'message' => 'Não foi possível cadastrar o usuário'
            ];
        }
        return $response;
    }
}
