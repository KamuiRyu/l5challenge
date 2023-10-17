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
        $request = request()->getBody();
        $array = $this->validationJson($request);
        if (isset($json['success']) && $json['success'] === false) {
            throw new \Exception($json['message'], 400);
        }

        $parametros = isset($array['parametros']) ? $array['parametros'] : null;

        if (empty($parametros)) {
            return $this->responseData(400, 'Os parametros não foram informados');
        }

        $validationErrors = $this->user_validation($parametros, 2);
        if (!empty($validationErrors)) {
            $retorno = array(
                'errors' => $validationErrors
            );
            return $this->responseData(400, 'Erro de validação', $retorno);
        }

        $UserModel = new \App\Models\UsuarioModel();
        $UserExisting = $UserModel->findUser("",$parametros['email']);

        if (empty($UserExisting)) {
            return $this->responseData(400, 'Este e-mail/senha não corresponde aos registro');
        }
        
        $pwd_verify = password_verify($parametros['senha'], $UserExisting->senha);

        if (!$pwd_verify) {
            return $this->responseData(401, 'Senha incorreta');
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

        return $this->responseData(200, 'Login efetuado com sucesso', $response);
    }
    public function register()
    {
        $request = request()->getBody();
        $array = $this->validationJson($request);
        if (isset($json['success']) && $json['success'] === false) {
            throw new \Exception($json['message'], 400);
        }

        $parametros = isset($array['parametros']) ? $array['parametros'] : null;

        if (empty($parametros)) {
            return $this->responseData(400, 'Os parametros não foram informados');
        }
        $validationErrors = $this->user_validation($parametros, 1);
        if (!empty($validationErrors)) {
            $retorno = array(
                'errors' => $validationErrors
            );
            return $this->responseData(400, 'Erro de validação', $retorno);
        }

        $UserModel = new \App\Models\UsuarioModel();
        $UserExisting = $UserModel->findUser("",$parametros['email']);

        if (!empty($UserExisting)) {
            return $this->responseData(400, 'Este email já está sendo utilizado por outro usuário');
        }
       
        $insertResult = $UserModel->create_user($parametros);
        if ($insertResult['success'] === false) {
            return $this->responseData(500, $insertResult['message']);
        }
        $responseData = array(
            'usuario' => $insertResult['user']
        );
        return $this->responseData(201, $insertResult['message'], $responseData);
    }

    public function validationJson($body)
    {
        $json = json_decode($body, true);
        if ($json === null) {
            return ['success' => false, 'message' => 'O formato do body é inválido. OBS: Utilizar JSON'];
        }
        return $json;
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

            default:

                break;
        }


        $errorMessages = [
            'required' => "O campo '%s' é obrigatório.",
            'not_null' => "O campo '%s' não pode ser nulo.",
            'min_length' => "O campo '%s' deve ter pelo menos %d caracteres.",
            'numeric' => "O campo '%s' deve ser numérico.",
            'integer' => "O campo '%s' deve ser um número inteiro.",
            'url' => "O campo '%s' deve ser uma URL válida.",
            'email' => "O campo '%s' deve ser um email válido.",
        ];

        $errors = [];

        if (isset($validationRules)) {
            foreach ($validationRules as $field => $rules) {

                $ruleList = explode('|', $rules);
                foreach ($ruleList as $rule) {
                    $ruleParts = explode('[', $rule, 2);
                    $ruleName = $ruleParts[0];
                    $param = $ruleParts[1] ?? null;

                    switch ($ruleName) {
                        case 'required':
                            if (!isset($data[$field]) || empty($data[$field])) {
                                $errors[] = sprintf($errorMessages[$ruleName], $field);
                            }
                            break;

                        case 'not_null':
                            if (isset($data[$field]) && empty($data[$field])) {
                                $errors[] = sprintf($errorMessages[$ruleName], $field);
                            }
                            break;

                        case 'min_length':
                            $minLength = intval(str_replace(']', '', $param));
                            if (isset($data[$field]) && strlen($data[$field]) < $minLength) {
                                $errors[] = sprintf($errorMessages[$ruleName], $field, $minLength);
                            }
                            break;

                        case 'numeric':
                            if (isset($data[$field]) && !is_numeric($data[$field])) {
                                $errors[] = sprintf($errorMessages[$ruleName], $field);
                            }
                            break;

                        case 'integer':
                            if (isset($data[$field]) && !filter_var($data[$field], FILTER_VALIDATE_INT)) {
                                $errors[] = sprintf($errorMessages[$ruleName], $field);
                            }
                            break;
                        case 'url':
                            if (isset($data[$field]) && !filter_var($data[$field], FILTER_VALIDATE_URL)) {
                                $errors[] = sprintf($errorMessages[$ruleName], $field);
                            }
                            break;
                        case 'url':
                            if (isset($data[$field]) && !filter_var($data[$field], FILTER_VALIDATE_EMAIL)) {
                                $errors[] = sprintf($errorMessages[$ruleName], $field);
                            }
                            break;
                    }
                }
            }
        }
        return $errors;
    }

    public function responseData($code, $message, $retorno = null)
    {
        $this->response->setStatusCode($code);
        $responseData = array(
            'cabecalho' => array(
                'status' => $code,
                'mensagem' => $message,

            )
        );
        if (!empty($retorno)) {
            $responseData['retorno'] = $retorno;
        }

        $this->response->setContentType('application/json');
        $this->response->setBody(json_encode($responseData));
        $this->response->send();
        return;
    }
}
