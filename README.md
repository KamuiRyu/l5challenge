# API de Gerenciamento de Clientes, Produtos e Pedidos

## Visão Geral

Esta é a documentação oficial para a API de Gerenciamento de Clientes, Produtos e Pedidos desenvolvida em Laravel. Esta API permite a criação, atualização, exclusão e busca de três tipos de dados principais: clientes, produtos e pedidos.

## Funcionalidades Principais

### Clientes
- **GET api/clientes**: Recupera os clientes de acordo com os filtros.
- **POST api/clientes**: Cria um novo cliente.
- **PUT api/clientes/{id}**: Atualiza os detalhes de um cliente existente.
- **DELETE api/clientes/{id}**: Exclui um cliente existente.

### Produtos
- **GET api/produtos**: Recupera os produtos de acordo com os filtros.
- **POST api/produtos**: Cria um novo produto.
- **PUT api/produtos/{id}**: Atualiza os detalhes de um produto existente.
- **DELETE api/produtos/{id}**: Exclui um produto existente.

### Pedidos
- **GET api/pedidos**: Recupera os pedidos de acordo com os filtros.
- **POST api/pedidos**: Cria um novo pedido.
- **PUT api/pedidos/{id}**: Atualiza os detalhes de um pedido existente.
- **DELETE api/pedidos/{id}**: Exclui um pedido existente.

### Usuários
- **POST api/register**: Cria um novo usuário.
- **POST api/login**: Cria a sessão do usuário e retorna o token.

## Configuração do Ambiente

Antes de usar esta API, você deve configurar seu ambiente de desenvolvimento. Certifique-se de ter o seguinte software instalado:

- Docker

### Siga os seguintes passos para configurar e executar a API:

Não se esqueça de realizar a configuração do .env, é muito importante preencher a váriavel "JWT_SECRET".

1. Clone este repositório em sua máquina local.
```bash
git clone https://github.com/KamuiRyu/l5challenge.git
```

2. Execute `docker-compose` para construir o container docker.
```bash
docker-compose up -d --build
```

3. Execute `docker exec` para executar comandos dentro do container do code igniter.
```bash
docker exec -it web bash
```

4. Execute os seguintes comandos para configurar o projeto.
```bash
composer install
```
```bash
php spark migrate
```


## Uso da API

Para utilizar a necessário uma ferramenta que realiza requisições HTTP, como o postman. Na pasta docs do projeto, tem o collection com todas as rotas disponíveis no projeto.

## Autorização

Todas as rotas, exceto `login` e `register`, requerem autorização. Para concluir uma requisição com sucesso, você deve incluir um token de autorização no cabeçalho da seguinte forma:

```
Authorization: Bearer {token}
``´

## Exemplos de Requisições

Aqui estão alguns exemplos de como fazer solicitações à API:

#### `POST /api/clientes`

```json
{
  "parametros":{
    "nome":"Dawin Nunes",
    "cpf_cnpj":"56909717074",
    "endereco":{
        "cep":"06520020",
        "numero":"6"
    }
  }
}
```

#### `DELETE /api/clientes/{id}`

#### `PUT /api/clientes/{id}`

```json
{
  "parametros":{
    "nome":"Dawin Nunes",
    "cpf_cnpj":"56909717074",
    "endereco":{
        "cep":"06520020",
        "numero":"6"
    }
  }
}
```

#### `GET /api/clientes`

```json
{
   "parametros":[
      {
         "campo":"endereco.cep",
         "condicao":"like",
         "valor":"05209470"
      },
      {
         "campo":"endereco.bairro",
         "condicao":"like",
         "valor":"Recanto dos Humildes"
      }
   ],
   "limit":100,
   "offset":0
}
```

Campos que podem ser utilizados no filtro:
#### `id`
- **Tipo**: int
- **Condições Permitidas**: =, <, >, <=, >=, like

#### `nome`
- **Tipo**: string
- **Condições permitidas**: =, ilike, like, <>

#### `cpf_cnpj`
- **Tipo**: string
- **Condições permitidas**: =, ilike, like, <>

- #### `created_at`
- **Tipo**: date
- **Condições permitidas**: =, <, >, <=, >=, between

- #### `updated_at`
- **Tipo**: date
- **Condições permitidas**: =, <, >, <=, >=, between

#### `endereco`
- **Condições permitidas**: like
- **Campos permitidos**: cep, logradouro, número, complemento, bairro, cidade, estado

#### `POST /api/produtos`

```json
{
  "parametros":{
    "nome":"Meia de lã",
    "descricao":"Confortável",
    "preco":"2.2",
    "quantidade_estoque":"2",
    "imagem_url":"https://encurtador.com.br/ceTX3"
  }
}
```

#### `DELETE /api/produtos/{id}`

#### `PUT /api/produtos/{id}`

```json
{
  "parametros":{
    "nome":"Meia de lã",
    "descricao":"Confortável",
    "preco":"2.2",
    "quantidade_estoque":"2",
    "imagem_url":"https://encurtador.com.br/ceTX3"
  }
}
```

#### `GET /api/produtos`

```json
{
	"parametros": [
		{
			"campo":"nome",
			"condicao":"ilike",
			"valor":"teste"
		},
		{
			"campo":"created_at",
			"condicao":"between",
			"valor":"18/10/2023",
			"valor2":"19/10/2023"
		}
	],
   "limit":100,
   "offset":0
}
```

Campos que podem ser utilizados no filtro:

#### `id`
- **Tipo**: int
- **Condições Permitidas**: =, <, >, <=, >=, like

#### `nome`
- **Tipo**: string
- **Condições permitidas**: =, ilike, like, <>

#### `descricao`
- **Tipo**: string
- **Condições permitidas**: =, ilike, like, <>

#### `preco`
- **Tipo**: decimal
- **Condições permitidas**: =, <, >, <=, >=, like

#### `quantidade_estoque`
- **Tipo**: int
- **Condições permitidas**: =, <, >, <=, >=, like

#### `imagem_url`
- **Tipo**: string
- **Condições permitidas**: =, ilike, like, <>

- #### `created_at`
- **Tipo**: date
- **Condições permitidas**: =, <, >, <=, >=, between

- #### `updated_at`
- **Tipo**: date
- **Condições permitidas**: =, <, >, <=, >=, between

#### `POST /api/pedidos`

```json
{
  "parametros":{
    "produto_id":"1",
    "cliente_id":"1",
    "preco":"2.2",
    "quantidade":"2",
    "data_pedido":"10/10/2023",
    "status": "em aberto"
  }
}
```

#### `DELETE /api/pedidos/{id}`

#### `PUT /api/pedidos/{id}`

```json
{
  "parametros":{
    "produto_id":"1",
    "cliente_id":"1",
    "preco":"2.2",
    "quantidade":"2",
    "data_pedido":"10/10/2023",
    "status": "em aberto"
  }
}
```

#### `GET /api/pedidos`

```json
{
	"parametros": [
		{
			"campo":"status",
			"condicao":"=",
			"valor":"em aberto"
		},
		{
			"campo":"created_at",
			"condicao":"between",
			"valor":"18/10/2023",
			"valor2":"19/10/2023"
		}
	],
   "limit":100,
   "offset":0
}
```

Campos que podem ser utilizados no filtro:

#### `id`
- **Tipo**: int
- **Condições Permitidas**: =, <, >, <=, >=, like

#### `produto_id`
- **Tipo**: int
- **Condições Permitidas**: =, <, >, <=, >=, like

#### `cliente_id`
- **Tipo**: int
- **Condições Permitidas**: =, <, >, <=, >=, like

#### `preco`
- **Tipo**: decimal
- **Condições permitidas**: =, <, >, <=, >=, like

#### `quantidade`
- **Tipo**: int
- **Condições permitidas**: =, <, >, <=, >=, like

#### `imagem_url`
- **Tipo**: string
- **Condições permitidas**: =, ilike, like, <>

#### `data_pedido`
- **Tipo**: date
- **Condições permitidas**: =, <, >, <=, >=, between

#### `status`
- **Tipo**: enum
- **Valores permitidos**: 'em aberto, pago, cancelado'
- **Condições permitidas**: =, <, >, <=, >=, between

#### `created_at`
- **Tipo**: date
- **Condições permitidas**: =, <, >, <=, >=, between

#### `updated_at`
- **Tipo**: date
- **Condições permitidas**: =, <, >, <=, >=, between

#### `POST /api/register`

```json
{
  "parametros":{
    "email":"teste@teste.com",
    "senha":"12345678"
  }
}
```

#### `POST /api/login`

```json
{
  "parametros":{
    "email":"teste@teste.com",
    "senha":"12345678"
  }
}
```


## Status das Respostas

A API retorna os seguintes códigos de status em suas respostas:

- 200 OK: Solicitação bem-sucedida.
- 201 Created: Recurso criado com sucesso.
- 204 No Content: Solicitação bem-sucedida, sem conteúdo a ser retornado.
- 400 Bad Request: Erro na solicitação do cliente.
- 404 Not Found: Recurso não encontrado.
- 500 Internal Server Error: Erro interno do servidor.

## Conclusão

Esta API oferece uma solução robusta para gerenciar clientes, produtos e pedidos. Sinta-se à vontade para explorar os endpoints e personalizar esta API de acordo com suas necessidades. Se você tiver alguma dúvida ou encontrar problemas, não hesite em entrar em contato com a equipe de desenvolvimento.

Divirta-se trabalhando com a API de Gerenciamento de Clientes, Produtos e Pedidos em Laravel!
