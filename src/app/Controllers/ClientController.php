<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use DateTime;
use Respect\Validation\Validator as v;

class ClientController extends BaseController
{
    public function __construct()
    {
    }
    public function create_client()
    {
        try {
            $request = request()->getBody();
            $array = $this->validationJson($request);
            if (isset($json['success']) && $json['success'] === false) {
                throw new \Exception($json['message'], 400);
            }
            $parametros = isset($array['parametros']) ? $array['parametros'] : null;

            if (empty($parametros)) {
                return $this->responseData(400, 'Os parametros não foram informados');
            }

            $validationErrors = $this->client_validation($parametros, 1);
            if (!empty($validationErrors)) {
                $retorno = array(
                    'errors' => $validationErrors
                );
                return $this->responseData(400, 'Erro de validação', $retorno);
            }

            $cepValidation = $this->CEPSearch($parametros['endereco']['cep']);
            if (isset($cepValidation['success']) && $cepValidation['success'] === false) {
                return $this->responseData(404, $cepValidation['message']);
            }


            $formattedData = $this->formatDataClient($parametros, $cepValidation);
            $ClienteModel = new \App\Models\ClienteModel();
            $clientExisting = $ClienteModel->findClient($formattedData['cpf_cnpj']);
            if (!empty($clientExisting) && $clientExisting > 0) {
                return $this->responseData(400, 'CNPJ ou CPF já existe em nossa base de dados.');
            }


            $insertResult = $ClienteModel->createClient($formattedData);
            if ($insertResult['success'] === false) {
                return $this->responseData(500, $insertResult['message']);
            }

            $responseData = array(
                'cliente' => $insertResult['client']
            );
            return $this->responseData(201, $insertResult['message'], $responseData);
        } catch (\Throwable $th) {
            $code = !empty($th->getCode()) ? $th->getCode() : 500;
            $message = $th->getMessage();
            return $this->responseData($code, $message);
        }
    }
    public function delete_client($id)
    {
        try {
            if (!is_numeric($id)) {
                return $this->responseData(400, 'O ID informado não é um número');
            }
            $ClienteModel = new \App\Models\ClienteModel();
            $clientExisting = $ClienteModel->findClient("", $id);
            if (empty($clientExisting)) {
                return $this->responseData(404, 'O usuário não foi encontrado em nossa base de dados');
            }

            $deleteClient = $ClienteModel->deleteClient($id);
            if ($deleteClient['success'] === false) {
                return $this->responseData(500, $deleteClient['message']);
            }
            return $this->responseData(204, $deleteClient['message']);
        } catch (\Throwable $th) {
            $code = !empty($th->getCode()) ? $th->getCode() : 500;
            $message = $th->getMessage();
            return $this->responseData($code, $message);
        }
    }

    public function update_client($id)
    {
        try {
            $request = request()->getBody();
            $array = $this->validationJson($request);
            if (isset($json['success']) && $json['success'] === false) {
                throw new \Exception($json['message'], 400);
            }
            $parametros = isset($array['parametros']) ? $array['parametros'] : null;
            if (empty($parametros)) {
                return $this->responseData(400, 'Os parametros não foram informados');
            }

            $validationErrors = $this->client_validation($parametros, 2);
            if (!empty($validationErrors)) {
                $retorno = array(
                    'errors' => $validationErrors
                );
                return $this->responseData(400, 'Erro de validação', $retorno);
            }

            $cepValidation = [];

            if ((isset($parametros['endereco']) && !empty($parametros['endereco'])) && (isset($parametros['endereco']['cep']) && !empty($parametros['endereco']['cep']))) {
                $cepValidation = $this->CEPSearch($parametros['endereco']['cep']);
                if (isset($cepValidation['success']) && $cepValidation['success'] === false) {
                    return $this->responseData(404, $cepValidation['message']);
                }
            }

            $formattedData = $this->formatDataClient($parametros, $cepValidation);
            $ClienteModel = new \App\Models\ClienteModel();
            $clientExisting = $ClienteModel->findClient("", $id);
            if (empty($clientExisting)) {
                return $this->responseData(404, 'O usuário não foi encontrado em nossa base de dados');
            }

            $updateResult = $ClienteModel->updateClient($formattedData, $id);
            if ($updateResult['success'] === false) {
                return $this->responseData(500, $updateResult['message']);
            }

            $responseData = array(
                'cliente' => $updateResult['data']
            );
            return $this->responseData(200, $updateResult['message'], $responseData);
        } catch (\Throwable $th) {
            $code = !empty($th->getCode()) ? $th->getCode() : 500;
            $message = $th->getMessage();
            return $this->responseData($code, $message);
        }
    }

    public function get_clients()
    {
        try {
            $request = request()->getBody();
            $array = $this->validationJson($request);
            if (isset($json['success']) && $json['success'] === false) {
                throw new \Exception($json['message'], 400);
            }
            $parametros = isset($array['parametros']) ? $array['parametros'] : null;

            if (empty($parametros)) {
                return $this->responseData(400, 'Os parametros não foram informados');
            }
            $offset = isset($array['offset']) ? intval($array['offset']) : 0;
            $limit = isset($array['limit']) ? $array['limit'] : 10;

            $validParameters = $this->validParameters($parametros, null);
            if($validParameters['success'] === false){
                $responseData = $validParameters['errors'];
                return $this->responseData(400, 'Os parametros não foram informados', $responseData);
            }
            $ClienteModel = new \App\Models\ClienteModel();
            $getClients = $ClienteModel->getClients($validParameters['data'], $offset, $limit, 1);
            
            if(!empty($getClients['data'])){
                $responseData = $getClients['data'];
            }else{
                $responseData = $getClients['message'];
            }
            return $this->responseData(200, 'Dados retornados com sucesso', $responseData);
        } catch (\Throwable $th) {
            $code = !empty($th->getCode()) ? $th->getCode() : 500;
            $message = $th->getMessage();
            return $this->responseData($code, $message);
        }
    }

    public function client_validation($data, $type)
    {
        switch ($type) {
            case 1:
                $validationRules = [
                    'nome' => ['required', 'min_length[3]'],
                    'cpf_cnpj' => ['required', 'cpf_cnpj'],
                    'endereco' => ['required', 'endereco_completo'],
                ];
                break;
            case 2:
                $validationRules = [
                    'nome' => ['not null', 'min_length[3]'],
                    'cpf_cnpj' => ['not null', 'cpf_cnpj'],
                    'endereco' => ['not null', 'endereco_completo'],
                ];
                break;
            default:
                $validationRules = [];
                break;
        }
        $errors = [];

        if (!empty($validationRules)) {
            foreach ($validationRules as $field => $rules) {
                foreach ($rules as $rule) {

                    if ($rule === 'required') {

                        if (!isset($data[$field]) || empty($data[$field])) {
                            $errors[] = "O campo '$field' é obrigatório.";
                        }
                    } else if ($rule === 'not null') {

                        if (isset($data[$field]) && empty($data[$field])) {
                            $errors[] = "O campo '$field' não pode ser nulo";
                        }
                    } elseif ($rule === 'cpf_cnpj') {
                        if (isset($data[$field]) && !$this->isValidCpfOrCnpj($data[$field])) {
                            $errors[] = "O campo '$field' não contém um CPF ou CNPJ válido.";
                        }
                    } elseif ($rule === 'endereco_completo') {
                        $endereco = isset($data[$field]) ? $data[$field] : null;
                        if (!empty($endereco) && (empty($endereco['cep']) || empty($endereco['numero']))) {
                            $errors[] = "O campo 'endereco' deve conter 'CEP' e 'numero' válidos.";
                        }
                    } elseif (strpos($rule, 'min_length') === 0) {
                        $minLength = intval(substr($rule, 11));
                        if (strlen($data[$field]) < $minLength) {
                            $errors[] = "O campo '$field' deve ter pelo menos $minLength caracteres.";
                        }
                    }
                }
            }
        }

        if (!empty($errors)) {
            return $errors;
        }
    }
    public function isValidCpfOrCnpj($value)
    {
        $value = preg_replace('/[^0-9]/', '', $value);

        if (strlen($value) === 11) {
            return v::cpf()->validate($value);
        } elseif (strlen($value) === 14) {
            return v::cnpj()->validate($value);
        }

        return false;
    }

    public function validationJson($body)
    {
        $json = json_decode($body, true);
        if ($json === null) {
            return ['success' => false, 'message' => 'O formato do body é inválido. OBS: Utilizar JSON'];
        }
        return $json;
    }

    public function formatDataClient($array, $endereco)
    {



        if (isset($endereco) && !empty($endereco)) {
            $addressData = json_decode($endereco);
        }

        if (isset($array['nome'])) {
            $formattedData['nome'] = ucwords(strtolower(trim($array['nome'])));
        }
        if (isset($array['cpf_cnpj'])) {
            $formattedData['cpf_cnpj'] = preg_replace("/[^0-9]/", "", $array['cpf_cnpj']);
        }
        if (isset($array['endereco']) && is_array($array['endereco'])) {
            $formattedData['endereco'] = [
                'cep' => isset($addressData->cep) ? str_replace('-', '', $addressData->cep) : null,
                'logradouro' => $addressData->logradouro ?? null,
                'número' => isset($array['endereco']['numero']) ? strtoupper(trim($array['endereco']['numero'])) : null,
                'complemento' => isset($array['endereco']['complemento']) ? $array['endereco']['complemento'] : $addressData->complemento,
                'bairro' => $addressData->bairro ?? null,
                'cidade' => $addressData->localidade ?? null,
                'estado' => $addressData->uf ?? null,
            ];
        }
        return $formattedData;
    }

    public function CEPSearch($cep)
    {
        if (!preg_match('/^\d{5}-?\d{3}$/', $cep)) {
            return ['success' => false, 'message' => 'CEP inválido'];
        }

        $url = "https://viacep.com.br/ws/{$cep}/json/";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        $respondeDecode = json_decode($response, true);
        if (empty($respondeDecode) || isset($respondeDecode['erro']) && $respondeDecode['erro'] === true) {
            return ['success' => false, 'message' => 'Falha na busca de CEP. CEP inexistente ou inválido'];
        } else {
            return $response;
        }
    }

    public function validParameters($data, $type)
    {
        $filters = [
            "id" => 'int',
            "cpf_cnpj" => 'string',
            "created_at" => 'date',
            "updated_at" => 'date',
            "endereco.cep" => 'string',
            "endereco.logradouro" => 'string',
            "endereco.bairro" => 'string',
            "endereco.cidade" => 'string',
            "endereco.estado" => 'string',
        ];
        $operators = [
            'between',
            'like',
            'ilike',
            '>',
            '<',
            '=',
            '>=',
            '<=',
            '<>',
        ];
        $validData = [];
        $errors = [];
        if (!empty($data)) {
            foreach ($data as $value) {
                $key = $value["campo"] ?? null;
                $condicao = isset($value['condicao']) && !empty($value['condicao']) ? strtolower($value['condicao']) : "=";
                $valor = $value["valor"] ?? null;
                $valor2 = $value["valor2"] ?? null;

                if (!empty($key)) {

                    if (array_key_exists($key, $filters)) {

                        $filterType = $filters[$key];
                        if ($filterType === 'int') {
                            if (is_numeric($valor) && is_int($valor + 0)) {
                                if ($condicao === 'between') {
                                    $errors[] = "A condição between não pode ser utilizada no campo '$key'";
                                } else if ($condicao === 'ilike') {
                                    $errors[] = "A condição ilike não pode ser utilizada no campo '$key'";
                                } else {

                                    $validData[$key]['valor'] = (int) $valor;
                                }
                            } else {
                                $errors[] = "O valor para '$key' deve ser um número inteiro.";
                            }
                        } elseif (is_array($filterType)) {
                            if (in_array($valor, $filterType)) {
                                if ($condicao === 'between') {
                                    $errors[] = "A condição between não pode ser utilizada no campo '$key'";
                                } else if ($condicao === '>=' || $condicao === '<=' || $condicao === '>' || $condicao === '>') {
                                    $errors[] = "A condição '$condicao' não pode ser utilizada no campo '$key'";
                                } else {
                                    $validData[$key]['valor'] = $valor;
                                }
                            } else {
                                $errors[] = "O valor para '$key' não está em uma lista parametros aceitos";
                            }
                        } elseif (!empty($key) && strpos($key, '.') !== false) {
                            list($keyMain, $keySecondary) = explode('.', $key, 2);
                            $validData[$keyMain][$keySecondary]['valor'] = $valor;
                        } elseif ($filterType === 'string') {
                            if ($condicao === 'between') {
                                $errors[] = "A condição between não pode ser utilizada no campo '$key'";
                            } else if ($condicao === '>=' || $condicao === '<=' || $condicao === '>' || $condicao === '>') {
                                $errors[] = "A condição '$condicao' não pode ser utilizada no campo '$key'";
                            } else {
                                $validData[$key]['valor'] = $valor;
                            }
                        } elseif ($filterType === 'date') {
                            $regex = '/^(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[0-2])\/(?:\d{2}|\d{4})$/';
                            if (preg_match($regex, $valor)) {
                                $formatsToTry = ['d-m-y', 'd-m-Y'];
                                $dateValue = str_replace('/', '-', $valor);
                                $date = null;
                                foreach ($formatsToTry as $format) {
                                    $date = DateTime::createFromFormat($format, $dateValue);
                                    if ($date != false) {
                                        break;
                                    }
                                }
                                if ($date instanceof DateTime) {
                                    $validData[$key]['valor'] = $date->format('Y-m-d');
                                } else {
                                    $errors[] = "O valor para '$key' não é uma data válida.";
                                }
                            } else {
                                $errors[] = "O valor para '$key' não é uma data válida.";
                            }
                            if ($condicao === 'between') {
                                if (isset($value['valor2']) && preg_match($regex, $valor2)) {
                                    $formatsToTry = ['d-m-y', 'd-m-Y'];
                                    $dateValue = str_replace('/', '-', $valor2);
                                    $date = null;
                                    foreach ($formatsToTry as $format) {
                                        $date = DateTime::createFromFormat($format, $dateValue);
                                        if ($date != false) {
                                            break;
                                        }
                                    }
                                    if ($date instanceof DateTime) {
                                        $validData[$key]['valor2'] = $date->format('Y-m-d');
                                    } else {
                                        $errors[] = "O valor para '$key' valor 2 não é uma data válida.";
                                    }
                                } else {
                                    $errors[] = "O valor 2 para '$key' é obrigatório na condição between.";
                                }
                            }
                        }
                        if (in_array($condicao, $operators)) {
                            if ((!empty($key) && strpos($key, '.') !== false)) {
                                list($keyMain, $keySecondary) = explode('.', $key, 2);
                                $validData[$keyMain][$keySecondary]['condicao'] = $condicao;
                            } else {
                                $validData[$key]['condicao'] = $condicao;
                            }
                        } else {
                            $errors[] = "A condição '$condicao' não é válida";
                        }
                    } else {
                        $errors[] = "O filtro '$key' não é permitido na consulta.";
                    }
                } else {
                    $errors[] = "O campo não foi informado no filtro";
                }
            }
            if (!empty($errors)) {
                return ['success' => false, 'errors' => $errors];
            }
        }
        return ['success' => true, 'data' => $validData];
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
