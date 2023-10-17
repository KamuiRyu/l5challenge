<?php

namespace App\Controllers;

use App\Controllers\BaseController;

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
            echo "<pre>";
            print_r($productExisting);
            echo "</pre>";
            die();
            
            if (!empty($productExisting)) {
                return $validationMethod->responseData(400, 'O produto não existe em nossa base de dados');
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

    public function order_validation($data, $type)
    {
        switch ($type) {
            case 1:
                $validationRules = [
                    'preco' => 'required|numeric',
                    'quantidade' => 'required|numeric',
                    'data_pedido' => 'required|date',
                    'status' => 'required|enum:Em aberto, Cancelado, Pago',
                    'produto' => 'required|array',
                    'cliente' => 'required|array'
                ];
                break;
            case 2:
                $validationRules = [
                    'preco' => 'not_null|numeric',
                    'quantidade' => 'not_null|numeric',
                    'data_pedido' => 'not_null|date',
                    'status' => 'not_null|enum:Em aberto, Cancelado, Pago',
                    'produto' => 'required|array',
                    'cliente' => 'required|array'
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
            $formattedData['nome'] = ucwords(strtolower(trim($array['preco'])));
        }

        if (isset($array['quantidade'])) {
            $formattedData['nome'] = ucwords(strtolower(trim($array['preco'])));
        }
        if (isset($array['data_pedido'])) {
            $formattedData['data_pedido'] = $array['data_pedido'];
        }

        if (isset($array['status'])) {
            $formattedData['status'] = ucwords(strtolower(trim($array['status'])));
        }
        if (isset($array['produto']) && is_array($array['produto'])) {
            $formattedData['produto'] = [
                'id' => isset($array['produto']['id']) ? $array['produto']['id'] : null,
                'nome' => $array['produto']['nome'] ?? null,
            ];
        }

        if (isset($array['cliente']) && is_array($array['cliente'])) {
            $formattedData['cliente'] = [
                'id' => isset($array['cliente']['id']) ? $array['cliente']['id'] : null,
                'nome' => $array['cliente']['nome'] ?? null,
            ];
        }
        return $formattedData;
    }
}
