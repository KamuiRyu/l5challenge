<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use DateTime;

class ProductController extends BaseController
{
    public function create_product()
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

            $validationErrors = $this->product_validation($parametros, 1);
            if (!empty($validationErrors)) {
                $retorno = array(
                    'errors' => $validationErrors
                );
                return $validationMethod->responseData(400, 'Erro de validação', $retorno);
            }

            $formattedData = $this->formatDataProduct($parametros);
            $ProdutoModel = new \App\Models\ProdutoModel();

            $insertResult = $ProdutoModel->createProduct($formattedData);
            if ($insertResult['success'] === false) {
                return $validationMethod->responseData(500, $insertResult['message']);
            }

            $responseData = array(
                'produto' => $insertResult['product']
            );
            return $validationMethod->responseData(201, $insertResult['message'], $responseData);
        } catch (\Throwable $th) {
            $code = !empty($th->getCode()) ? $th->getCode() : 500;
            $message = $th->getMessage();
            return $validationMethod->responseData($code, $message);
        }
    }

    public function delete_product($id)
    {
        try {
            $validationMethod = new ValidationController;
            if (!is_numeric($id)) {
                return $validationMethod->responseData(400, 'O ID informado não é um número');
            }
            $ProdutoModel = new \App\Models\ProdutoModel();
            $productExisting = $ProdutoModel->findProduct($id);
            if (empty($productExisting)) {
                return $validationMethod->responseData(404, 'O usuário não foi encontrado em nossa base de dados');
            }

            $deleteProduct = $ProdutoModel->deleteProduct($id);
            if ($deleteProduct['success'] === false) {
                return $validationMethod->responseData(500, $deleteProduct['message']);
            }
            return $validationMethod->responseData(204, $deleteProduct['message']);
        } catch (\Throwable $th) {
            $code = !empty($th->getCode()) ? $th->getCode() : 500;
            $message = $th->getMessage();
            return $validationMethod->responseData($code, $message);
        }
    }

    public function update_product($id)
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

            $validationErrors = $this->product_validation($parametros, 2);
            if (!empty($validationErrors)) {
                $retorno = array(
                    'errors' => $validationErrors
                );
                return $validationMethod->responseData(400, 'Erro de validação', $retorno);
            }

            $formattedData = $this->formatDataProduct($parametros);
            $ProdutoModel = new \App\Models\ProdutoModel();
            $productExisting = $ProdutoModel->findProduct($id);
            if (empty($productExisting)) {
                return $validationMethod->responseData(404, 'O produto não foi encontrado em nossa base de dados');
            }

            $updateResult = $ProdutoModel->updateProduct($formattedData, $id);
            if ($updateResult['success'] === false) {
                return $validationMethod->responseData(500, $updateResult['message']);
            }

            $responseData = array(
                'produtos' => $updateResult['data']
            );
            return $validationMethod->responseData(200, $updateResult['message'], $responseData);
        } catch (\Throwable $th) {
            $code = !empty($th->getCode()) ? $th->getCode() : 500;
            $message = $th->getMessage();
            return $validationMethod->responseData($code, $message);
        }
    }

    public function get_products()
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
            $ProdutoModel = new \App\Models\ProdutoModel();
            $getProducts = $ProdutoModel->getProducts($validParameters['data'], $offset, $limit, 1);
            
            if(!empty($getProducts['data'])){
                $responseData = $getProducts['data'];
            }else{
                $responseData = $getProducts['message'];
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
        $validationMethod = new ValidationController;
        return $validationMethod->validateFilters($data, $filters, $operators, $type);
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
        $validationMethod = new ValidationController;
        $validationErrors = $validationMethod->validation($data, $validationRules);

        return $validationErrors;
    }
}
