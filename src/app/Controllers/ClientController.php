<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use DateTime;


class ClientController extends BaseController
{

    public function create_client()
    {
        try {
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

            $validationErrors = $this->client_validation($parametros, 1);
            if (!empty($validationErrors)) {
                $retorno = array(
                    'errors' => $validationErrors
                );
                return $validationMethod->responseData(400, 'Erro de validação', $retorno);
            }

            $cepValidation = $validationMethod->CEPSearch($parametros['endereco']['cep']);
            if (isset($cepValidation['success']) && $cepValidation['success'] === false) {
                return $validationMethod->responseData(404, $cepValidation['message']);
            }


            $formattedData = $this->formatDataClient($parametros, $cepValidation);
            $ClienteModel = new \App\Models\ClienteModel();
            $clientExisting = $ClienteModel->findClient($formattedData['cpf_cnpj']);
            if (!empty($clientExisting) && $clientExisting > 0) {
                return $validationMethod->responseData(400, 'CNPJ ou CPF já existe em nossa base de dados.');
            }


            $insertResult = $ClienteModel->createClient($formattedData);
            if ($insertResult['success'] === false) {
                return $validationMethod->responseData(500, $insertResult['message']);
            }

            $responseData = array(
                'cliente' => $insertResult['client']
            );
            return $validationMethod->responseData(201, $insertResult['message'], $responseData);
        } catch (\Throwable $th) {
            $code = !empty($th->getCode()) ? $th->getCode() : 500;
            $message = $th->getMessage();
            return $validationMethod->responseData($code, $message);
        }
    }
    public function delete_client($id)
    {
        try {
            $validationMethod = new ValidationController;
            if (!is_numeric($id)) {
                return $validationMethod->responseData(400, 'O ID informado não é um número');
            }
            $ClienteModel = new \App\Models\ClienteModel();
            $clientExisting = $ClienteModel->findClient("", $id);
            if (empty($clientExisting)) {
                return $validationMethod->responseData(404, 'O usuário não foi encontrado em nossa base de dados');
            }

            $deleteClient = $ClienteModel->deleteClient($id);
            if ($deleteClient['success'] === false) {
                return $validationMethod->responseData(500, $deleteClient['message']);
            }
            return $validationMethod->responseData(204, $deleteClient['message']);
        } catch (\Throwable $th) {
            $code = !empty($th->getCode()) ? $th->getCode() : 500;
            $message = $th->getMessage();
            return $validationMethod->responseData($code, $message);
        }
    }

    public function update_client($id)
    {
        try {
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

            $validationErrors = $this->client_validation($parametros, 2);
            if (!empty($validationErrors)) {
                $retorno = array(
                    'errors' => $validationErrors
                );
                return $validationMethod->responseData(400, 'Erro de validação', $retorno);
            }

            $cepValidation = [];

            if ((isset($parametros['endereco']) && !empty($parametros['endereco'])) && (isset($parametros['endereco']['cep']) && !empty($parametros['endereco']['cep']))) {
                $cepValidation = $validationMethod->CEPSearch($parametros['endereco']['cep']);
                if (isset($cepValidation['success']) && $cepValidation['success'] === false) {
                    return $validationMethod->responseData(404, $cepValidation['message']);
                }
            }

            $formattedData = $this->formatDataClient($parametros, $cepValidation);
            $ClienteModel = new \App\Models\ClienteModel();
            $clientExisting = $ClienteModel->findClient("", $id);
            if (empty($clientExisting)) {
                return $validationMethod->responseData(404, 'O usuário não foi encontrado em nossa base de dados');
            }

            $updateResult = $ClienteModel->updateClient($formattedData, $id);
            if ($updateResult['success'] === false) {
                return $validationMethod->responseData(500, $updateResult['message']);
            }

            $responseData = array(
                'cliente' => $updateResult['data']
            );
            return $validationMethod->responseData(200, $updateResult['message'], $responseData);
        } catch (\Throwable $th) {
            $code = !empty($th->getCode()) ? $th->getCode() : 500;
            $message = $th->getMessage();
            return $validationMethod->responseData($code, $message);
        }
    }

    public function get_clients()
    {
        try {
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
            $offset = isset($array['offset']) ? intval($array['offset']) : 0;
            $limit = isset($array['limit']) ? $array['limit'] : 10;

            $validParameters = $this->validParameters($parametros, null);
            if($validParameters['success'] === false){
                $responseData = $validParameters['errors'];
                return $validationMethod->responseData(400, 'Os parametros não foram informados', $responseData);
            }
            $ClienteModel = new \App\Models\ClienteModel();
            $getClients = $ClienteModel->getClients($validParameters['data'], $offset, $limit, 1);
            
            if(!empty($getClients['data'])){
                $responseData = $getClients['data'];
            }else{
                $responseData = $getClients['message'];
            }
            return $validationMethod->responseData(200, 'Dados retornados com sucesso', $responseData);
        } catch (\Throwable $th) {
            $code = !empty($th->getCode()) ? $th->getCode() : 500;
            $message = $th->getMessage();
            return $validationMethod->responseData($code, $message);
        }
    }

    public function client_validation($data, $type)
    {
        switch ($type) {
            case 1:
                $validationRules = [
                    'nome' => 'required|min_length[3]',
                    'cpf_cnpj' => 'required|cpf_cnpj',
                    'endereco' => 'required|endereco_completo',
                ];
                break;
            case 2:
                $validationRules = [
                    'nome' => 'not null|min_length[3]',
                    'cpf_cnpj' => 'not null|cpf_cnpj',
                    'endereco' => 'not null|endereco_completo',
                ];
                break;
            default:
                $validationRules = [];
                break;
        }

        $validationMethod = new ValidationController;

        return $validationMethod->validation($data, $validationRules);
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
        $validationMethod = new ValidationController;
        return $validationMethod->validateFilters($data, $filters, $operators, $type);
    }
}
