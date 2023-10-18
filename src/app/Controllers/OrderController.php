<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use DateTime;

class OrderController extends BaseController
{
    public function create_order()
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

            $validationErrors = $this->order_validation($parametros, 1);
            if (!empty($validationErrors)) {
                $retorno = array(
                    'errors' => $validationErrors
                );
                return $validationMethod->responseData(400, 'Erro de validação', $retorno);
            }

            $formattedData = $this->formatDataOrder($parametros);
            $ProdutoModel = new \App\Models\ProdutoModel();
            $productExisting = $ProdutoModel->findProduct($formattedData['produto']['id']);

            if (empty($productExisting)) {
                return $validationMethod->responseData(400, 'O produto não existe em nossa base de dados');
            }


            $ClienteModel = new \App\Models\ClienteModel();
            $clientExisting = $ClienteModel->findClient("", $formattedData['cliente']['id']);

            if (empty($clientExisting)) {
                return $validationMethod->responseData(400, 'O cliente não existe em nossa base de dados');
            }


            $OrderModel = new \App\Models\OrderModel();
            $insertResult = $OrderModel->createOrder($formattedData);
            if ($insertResult['success'] === false) {
                return $validationMethod->responseData(500, $insertResult['message']);
            }

            $responseData = array(
                'pedido' => $insertResult['pedido']
            );
            return $validationMethod->responseData(201, $insertResult['message'], $responseData);
        } catch (\Throwable $th) {
            $code = !empty($th->getCode()) ? $th->getCode() : 500;
            $message = $th->getMessage();
            return $validationMethod->responseData($code, $message);
        }
    }

    public function delete_order($id)
    {
        try {
            $validationMethod = new ValidationController;
            if (!is_numeric($id)) {
                return $validationMethod->responseData(400, 'O ID informado não é um número');
            }
            $OrderModel = new \App\Models\OrderModel();
            $orderExisting = $OrderModel->findOrder($id);
            if (empty($orderExisting)) {
                return $validationMethod->responseData(404, 'o pedido não foi encontrado em nossa base de dados');
            }

            $deleteOrder = $OrderModel->deleteOrder($id);
            if ($deleteOrder['success'] === false) {
                return $validationMethod->responseData(500, $deleteOrder['message']);
            }
            return $validationMethod->responseData(204, $deleteOrder['message']);
        } catch (\Throwable $th) {
            $code = !empty($th->getCode()) ? $th->getCode() : 500;
            $message = $th->getMessage();
            return $validationMethod->responseData($code, $message);
        }
    }

    public function update_order($id)
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

            $validationErrors = $this->order_validation($parametros, 2);
            if (!empty($validationErrors)) {
                $retorno = array(
                    'errors' => $validationErrors
                );
                return $validationMethod->responseData(400, 'Erro de validação', $retorno);
            }

            $formattedData = $this->formatDataOrder($parametros);
            if(isset($formattedData['produto'])){
                $ProdutoModel = new \App\Models\ProdutoModel();
                $productExisting = $ProdutoModel->findProduct($formattedData['produto']['id']);
    
                if (empty($productExisting)) {
                    return $validationMethod->responseData(400, 'O produto não existe em nossa base de dados');
                }
            }
            

            if(isset($formattedData['cliente'])){
                $ClienteModel = new \App\Models\ClienteModel();
                $clientExisting = $ClienteModel->findClient("", $formattedData['cliente']['id']);
    
                if (empty($clientExisting)) {
                    return $validationMethod->responseData(400, 'O cliente não existe em nossa base de dados');
                }
            }
            $OrderModel = new \App\Models\OrderModel();
            $updateResult = $OrderModel->updateOrder($formattedData, $id);
            if ($updateResult['success'] === false) {
                return $validationMethod->responseData(500, $updateResult['message']);
            }

            $responseData = array(
                'pedido' => $updateResult['data']
            );
            return $validationMethod->responseData(200, $updateResult['message'], $responseData);
        } catch (\Throwable $th) {
            $code = !empty($th->getCode()) ? $th->getCode() : 500;
            $message = $th->getMessage();
            return $validationMethod->responseData($code, $message);
        }
    }

    public function get_orders()
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
            $OrderModel = new \App\Models\OrderModel();
            $getOrders = $OrderModel->getOrders($validParameters['data'], $offset, $limit, 1);

            if(!empty($getOrders['data'])){
                $responseData = $getOrders['data'];
            }else{
                $responseData = $getOrders['message'];
            }
            return $validationMethod->responseData(200, 'Dados retornados com sucesso', $responseData);
        } catch (\Throwable $th) {
            $code = !empty($th->getCode()) ? $th->getCode() : 500;
            $message = $th->getMessage();
            return $validationMethod->responseData($code, $message);
        }
    }

    public function validParameters($data, $type)
    {
        $filters = [
            "id" => 'int',
            "preco" => 'int',
            "quantidade" => 'int',
            "data_pedido" => 'date',
            "status" => ['em aberto', 'pago', 'cancelado'],
            "cliente_id" => 'int',
            "produto_id" => 'int',
            "updated_at" => 'date',
            "created_at" => 'date',
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

    public function order_validation($data, $type)
    {
        switch ($type) {
            case 1:
                $validationRules = [
                    'preco' => 'required|numeric',
                    'quantidade' => 'required|numeric',
                    'data_pedido' => 'required|date',
                    'status' => 'required|enum:Em aberto, Cancelado, Pago',
                    'produto_id' => 'required|numeric',
                    'cliente_id' => 'required|numeric'
                ];
                break;
            case 2:
                $validationRules = [
                    'preco' => 'not_null|numeric',
                    'quantidade' => 'not_null|numeric',
                    'data_pedido' => 'not_null|date',
                    'status' => 'not_null|enum:Em aberto, Cancelado, Pago',
                    'produto_id' => 'not_null|numeric',
                    'cliente_id' => 'not_null|numeric'
                ];
                break;
            default:
                $validationRules = [];
                break;
        }

        $validationMethod = new ValidationController;

        return $validationMethod->validation($data, $validationRules);
    }

    public function formatDataOrder($array)
    {

        if (isset($array['preco'])) {
            $formattedData['preco'] = ucwords(strtolower(trim($array['preco'])));
        }

        if (isset($array['quantidade'])) {
            $formattedData['quantidade'] = ucwords(strtolower(trim($array['quantidade'])));
        }
        if (isset($array['data_pedido'])) {
            $formattedData['data_pedido'] = $array['data_pedido'];
            $formatsToTry = ['d-m-y', 'd-m-Y'];
            $dateValue = str_replace('/', '-', $array['data_pedido']);
            $date = null;
            foreach ($formatsToTry as $format) {
                $date = DateTime::createFromFormat($format, $dateValue);
                if ($date != false) {
                    break;
                }
            }
            if ($date instanceof DateTime) {
                $formattedData['data_pedido'] = $date->format('Y-m-d');
            }
        }

        if (isset($array['status'])) {
            $formattedData['status'] = ucwords(strtolower(trim($array['status'])));
        }
        if (isset($array['produto']) && is_array($array['produto'])) {
            $formattedData['produto'] = [
                'id' => isset($array['produto']['id']) ? $array['produto']['id'] : null,
            ];
        }

        if (isset($array['cliente']) && is_array($array['cliente'])) {
            $formattedData['cliente'] = [
                'id' => isset($array['cliente']['id']) ? $array['cliente']['id'] : null,
            ];
        }
        return $formattedData;
    }
}
