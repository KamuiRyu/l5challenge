<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use Firebase\JWT\JWT;

class UserController extends BaseController
{
    use ResponseTrait;

    public function login()
    {
        $validationMethod = new ValidationController;
        $request = request()->getBody();
        $array = $validationMethod->validationJson($request);
        if (isset($json['success']) && $json['success'] === false) {
            throw new \Exception($json['message'], 400);
        }

        $parametros = isset($array['parametros']) ? $array['parametros'] : null;

        if (empty($parametros)) {
            return $validationMethod->responseData(400, 'Os parametros não foram informados');
        }

        $validationErrors = $this->user_validation($parametros, 2);
        if (!empty($validationErrors)) {
            $retorno = array(
                'errors' => $validationErrors
            );
            return $validationMethod->responseData(400, 'Erro de validação', $retorno);
        }

        $UserModel = new \App\Models\UsuarioModel();
        $UserExisting = $UserModel->findUser("", $parametros['email']);

        if (empty($UserExisting)) {
            return $validationMethod->responseData(400, 'Este e-mail/senha não corresponde aos registro');
        }

        $pwd_verify = password_verify($parametros['senha'], $UserExisting->senha);
        
        if (!$pwd_verify) {
            return $validationMethod->responseData(401, 'Senha incorreta');
        }

        $key = getenv('JWT_SECRET');
        $iat = time();
        $exp = $iat + 3600;

        $payload = array(
            "iat" => $iat,
            "exp" => $exp,
            "email" => $UserExisting->email,
        );

        $token = JWT::encode($payload, $key, 'HS256');
        $response = [
            'token' => $token
        ];
        
        return $validationMethod->responseData(200, 'Login efetuado com sucesso', $response);
    }
    public function register()
    {
        $validationMethod = new ValidationController();
        $request = request()->getBody();
        $array = $validationMethod->validationJson($request);
        
        
        if (isset($json['success']) && $json['success'] === false) {
            throw new \Exception($json['message'], 400);
        }

        $parametros = isset($array['parametros']) ? $array['parametros'] : null;
        if (empty($parametros)) {
            return $validationMethod->responseData(400, 'Os parametros não foram informados');
        }
        $validationErrors = $this->user_validation($parametros, 1);
        if (!empty($validationErrors)) {
            $retorno = array(
                'errors' => $validationErrors
            );
            return $validationMethod->responseData(400, 'Erro de validação', $retorno);
        }

        $UserModel = new \App\Models\UsuarioModel();
        $UserExisting = $UserModel->findUser("", $parametros['email']);

        if (!empty($UserExisting)) {
            return $validationMethod->responseData(400, 'Este email já está sendo utilizado por outro usuário');
        }

        $insertResult = $UserModel->create_user($parametros);
        if ($insertResult['success'] === false) {
            return $validationMethod->responseData(500, $insertResult['message']);
        }
        $responseData = array(
            'usuario' => $insertResult['user']
        );
        return $validationMethod->responseData(201, $insertResult['message'], $responseData);
    }

    
    public function user_validation($data, $type)
    {
        switch ($type) {
            case 1:
                $validationRules = [
                    'email' => 'required|email',
                    'senha' => 'required|min_length[8]',
                ];
                break;
            case 2:
                $validationRules = [
                    'email' => 'not_null|email',
                    'senha' => 'not_null',
                ];
                break;
        }

        $validationMethod = new ValidationController;
        $validationErrors = $validationMethod->validation($data, $validationRules);

        return $validationErrors;
    }

    
}
