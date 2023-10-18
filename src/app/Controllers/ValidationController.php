<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use DateTime;
use Respect\Validation\Validator as v;

class ValidationController extends BaseController
{
    public function validation($data, $validationRules)
    {
        $errorMessages = [
            'required' => "O campo '%s' é obrigatório.",
            'not_null' => "O campo '%s' não pode ser nulo.",
            'min_length' => "O campo '%s' deve ter pelo menos %d caracteres.",
            'numeric' => "O campo '%s' deve ser numérico.",
            'integer' => "O campo '%s' deve ser um número inteiro.",
            'url' => "O campo '%s' deve ser uma URL válida.",
            'email' => "O campo '%s' deve ser um email válido.",
            'cpf_cnpj' => "O campo '%s contém um CPF ou CNPJ válido.",
            'endereco_completo' => "O campo '%s' deve conter 'CEP' e 'numero' válidos.",
            'array' => "O campo '%s' deve conter 'ID' válidos.",
            'enum' => "O campo '%s' só aceita os seguintes valores: %s",
            'date' => "O campo '%s' deve ser uma data válida. EX: 10/08/2023 "
        ];

        $errors = [];
        if (isset($validationRules)) {
            foreach ($validationRules as $field => $rules) {

                $ruleList = explode('|', $rules);
                foreach ($ruleList as $rule) {
                    $ruleParts = explode('[', $rule, 2);
                    $ruleName = $ruleParts[0];
                    $param = $ruleParts[1] ?? null;
                    if (!empty($ruleName) && strpos($ruleName, ':') !== false) {
                        $array = explode(':', $ruleName, 2);
                        $valuesAccept = explode(',', $array[1]);
                        $ruleName = $array[0];
                    }
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
                        case 'date':
                            if (isset($data[$field])) {
                                $date = $data[$field];
                                $dateFormats = ['d/m/Y', 'd/m/Y', 'd/m/y', 'd/m/y'];
                                $isValidFormat = false;
                                foreach ($dateFormats as $format) {
                                    $dateTime = DateTime::createFromFormat($format, $date);
                                    if ($dateTime && $date === $dateTime->format($format)) {
                                        $isValidFormat = true;
                                        break;
                                    }
                                }

                                if (!$isValidFormat) {
                                    $errors[] = sprintf($errorMessages[$ruleName], $field);
                                }
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
                        case 'email':
                            if (isset($data[$field]) && !filter_var($data[$field], FILTER_VALIDATE_EMAIL)) {
                                $errors[] = sprintf($errorMessages[$ruleName], $field);
                            }
                            break;
                        case 'cpf_cnpj':
                            if (isset($data[$field]) && !$this->isValidCpfOrCnpj($data[$field])) {
                                $errors[] = sprintf($errorMessages[$ruleName], $field);
                            }
                            break;
                        case 'endereco_completo':
                            $endereco = isset($data[$field]) ? $data[$field] : null;
                            if (!empty($endereco) && (empty($endereco['cep']) || empty($endereco['numero']))) {
                                $errors[] = sprintf($errorMessages[$ruleName], $field);
                            }
                            break;
                        case 'array':
                            
                            
                            if (isset($data[$field])) {

                                $array = isset($data[$field]) ? $data[$field] : null;
                                if (!empty($array) && empty($array['id'])) {
                                    $errors[] = sprintf($errorMessages[$ruleName], $field);
                                }
                                if (isset($array['id']) && !is_numeric($array['id'])) {
                                    $errors[] = sprintf($errorMessages['integer'], $field . ".id");
                                }
                            }
                            break;
                        case 'enum':
                            if (isset($data[$field])) {
                                if (isset($valuesAccept) && !empty($valuesAccept)) {
                                    $isValid = false;
                                    $fieldValue = strtolower(trim($data[$field]));
                                    $trimmedValuesAccept = array_map('trim', $valuesAccept);
                                    $acceptedValuesList = implode(', ', $trimmedValuesAccept);
                                    foreach ($valuesAccept as $accept) {
                                        if (strtolower(trim($accept)) === $fieldValue) {
                                            $isValid = true;
                                            break;
                                        }
                                    }
                                    if (!$isValid) {
                                        $errors[] = sprintf($errorMessages[$ruleName], $field, $acceptedValuesList);
                                    }
                                }
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
        $response = service('response');
        $response->setStatusCode($code);
        $responseData = array(
            'cabecalho' => array(
                'status' => $code,
                'mensagem' => $message,

            )
        );
        if (!empty($retorno)) {
            $responseData['retorno'] = $retorno;
        }

        $response->setContentType('application/json');
        $response->setBody(json_encode($responseData));
        $response->send();
        return;
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

    public function validateFilters($data, $filters, $operators, $type)
    {
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

    public function validationJson($body)
    {
        $json = json_decode($body, true);
        if ($json === null) {
            return ['success' => false, 'message' => 'O formato do body é inválido. OBS: Utilizar JSON'];
        }
        return $json;
    }
}
