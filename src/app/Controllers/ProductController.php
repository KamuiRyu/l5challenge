<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use DateTime;

class ProductController extends BaseController
{
    public function create_product()
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

            $validationErrors = $this->product_validation($parametros, 1);
            if (!empty($validationErrors)) {
                $retorno = array(
                    'errors' => $validationErrors
                );
                return $this->responseData(400, 'Erro de validação', $retorno);
            }

            $formattedData = $this->formatDataProduct($parametros);
            $ProdutoModel = new \App\Models\ProdutoModel();

            $insertResult = $ProdutoModel->createProduct($formattedData);
            if ($insertResult['success'] === false) {
                return $this->responseData(500, $insertResult['message']);
            }

            $responseData = array(
                'produto' => $insertResult['product']
            );
            return $this->responseData(201, $insertResult['message'], $responseData);
        } catch (\Throwable $th) {
            $code = !empty($th->getCode()) ? $th->getCode() : 500;
            $message = $th->getMessage();
            return $this->responseData($code, $message);
        }
    }

    public function delete_product($id)
    {
        try {
            if (!is_numeric($id)) {
                return $this->responseData(400, 'O ID informado não é um número');
            }
            $ProdutoModel = new \App\Models\ProdutoModel();
            $productExisting = $ProdutoModel->findProduct($id);
            if (empty($productExisting)) {
                return $this->responseData(404, 'O usuário não foi encontrado em nossa base de dados');
            }

            $deleteProduct = $ProdutoModel->deleteProduct($id);
            if ($deleteProduct['success'] === false) {
                return $this->responseData(500, $deleteProduct['message']);
            }
            return $this->responseData(204, $deleteProduct['message']);
        } catch (\Throwable $th) {
            $code = !empty($th->getCode()) ? $th->getCode() : 500;
            $message = $th->getMessage();
            return $this->responseData($code, $message);
        }
    }

    public function update_product($id)
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

            $validationErrors = $this->product_validation($parametros, 2);
            if (!empty($validationErrors)) {
                $retorno = array(
                    'errors' => $validationErrors
                );
                return $this->responseData(400, 'Erro de validação', $retorno);
            }

            $formattedData = $this->formatDataProduct($parametros);
            $ProdutoModel = new \App\Models\ProdutoModel();
            $productExisting = $ProdutoModel->findProduct($id);
            if (empty($productExisting)) {
                return $this->responseData(404, 'O produto não foi encontrado em nossa base de dados');
            }

            $updateResult = $ProdutoModel->updateProduct($formattedData, $id);
            if ($updateResult['success'] === false) {
                return $this->responseData(500, $updateResult['message']);
            }

            $responseData = array(
                'produtos' => $updateResult['data']
            );
            return $this->responseData(200, $updateResult['message'], $responseData);
        } catch (\Throwable $th) {
            $code = !empty($th->getCode()) ? $th->getCode() : 500;
            $message = $th->getMessage();
            return $this->responseData($code, $message);
        }
    }

    public function get_products()
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
            $ProdutoModel = new \App\Models\ProdutoModel();
            $getProducts = $ProdutoModel->getProducts($validParameters['data'], $offset, $limit, 1);
            
            if(!empty($getProducts['data'])){
                $responseData = $getProducts['data'];
            }else{
                $responseData = $getProducts['message'];
            }
            return $this->responseData(200, 'Dados retornados com sucesso', $responseData);
        } catch (\Throwable $th) {
            $code = !empty($th->getCode()) ? $th->getCode() : 500;
            $message = $th->getMessage();
            return $this->responseData($code, $message);
        }
    }

    public function validParameters($data, $type)
    {
        $filters = [
            "id" => 'int',
            "nome" => 'string',
            "descricao" => 'string',
            "updated_at" => 'date',
            "created_at" => 'date',
            "preco" => 'int',
            "quantidade_estoque" => 'int',
            "created_at" => 'date',
            "updated_at" => 'date',
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

    public function formatDataProduct($array)
    {
        if (isset($array['nome'])) {
            $formattedData['nome'] = ucwords(strtolower(trim($array['nome'])));
        }
        if (isset($array['preco'])) {
            $formattedData['preco'] = str_replace(',', '.', $array['preco']);
        }

        if (isset($array['descricao'])) {
            $formattedData['descricao'] = trim($array['descricao']);
        }

        if (isset($array['quantidade_estoque'])) {
            $formattedData['quantidade_estoque'] = (int) $array['quantidade_estoque'];
        }

        if (isset($array['imagem_url'])) {
            $formattedData['imagem_url'] = trim($array['imagem_url']);
        }

        return $formattedData;
    }

    public function product_validation($data, $type)
    {
        switch ($type) {
            case 1:
                $validationRules = [
                    'nome' => 'required|min_length[3]',
                    'descricao' => 'not_null',
                    'preco' => 'required|numeric',
                    'quantidade_estoque' => 'required|integer',
                    'imagem_url' => 'required|url',
                ];
                break;
            case 2:
                $validationRules = [
                    'nome' => 'not_null|min_length[3]',
                    'descricao' => 'not_null',
                    'preco' => 'not_null|numeric',
                    'quantidade_estoque' => 'not_null|integer',
                    'imagem_url' => 'not_null|url',
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
                    }
                }
            }
        }
        return $errors;
    }


    public function validationJson($body)
    {
        $json = json_decode($body, true);
        if ($json === null) {
            return ['success' => false, 'message' => 'O formato do body é inválido. OBS: Utilizar JSON'];
        }
        return $json;
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
