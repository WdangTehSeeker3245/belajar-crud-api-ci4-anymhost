<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
use CodeIgniter\RESTful\ResourceController;
use App\Models\ProductModel;

class ProductsController extends ResourceController
{
    use ResponseTrait;

    protected $format    = 'json';

    public function __construct()
    {
        $this->model = new ProductModel();
    }

    /**
     * Return an array of resource objects, themselves in array format
     *
     * @return mixed
     */
    public function index()
    {
        $products = $this->model->findAll();
        return $this->respond($products);
    }

    /**
     * Return the properties of a resource object
     *
     * @return mixed
     */
    public function show($id = null)
    {
        $product = $this->model->find($id);
        if (!$product) {
            return $this->failNotFound('Product not found.');
        }
        return $this->respond($product);
    }

    /**
     * Return a new resource object, with default properties
     *
     * @return mixed
     */
    public function new()
    {
        //
    }

    /**
     * Create a new resource object, from "posted" parameters
     *
     * @return mixed
     */
    public function create()
    {   
        $data = $this->request->getJSON(true);

        // Validate input data
        $validation =  \Config\Services::validation();
        $validation->setRules([
            'name' => 'required',
            'price' => 'required|numeric',
        ]);

        if (!$validation->run($data)) {
            return $this->failValidationErrors($validation->getErrors());
        }

        $this->model->insert($data);
        $response = [
            'status' => 201,
            'error' => null,
            'message' => 'Product created successfully.'
        ];
        
        return $this->respondCreated($response);
    }
    

    /**
     * Return the editable properties of a resource object
     *
     * @return mixed
     */
    public function edit($id = null)
    {
        //
    }

    /**
     * Add or update a model resource, from "posted" properties
     *
     * @return mixed
     */
    public function update($id = null)
    {   
        $data = $this->request->getJSON(true);

        // Validate input data
        $validation =  \Config\Services::validation();
        $validation->setRules([
            'name' => 'required',
            'price' => 'required|numeric',
        ]);

        if (!$validation->run($data)) {
            return $this->failValidationErrors($validation->getErrors());
        }

        $product = $this->model->find($id);
        
        if (!$product) {
            return $this->failNotFound('Product not found.');
        }

        $this->model->update($id, $data);
        $response = [
            'status' => 200,
            'error' => null,
            'message' => 'Product updated successfully.'
        ];
        
        return $this->respond($response);
    }


    /**
     * Delete the designated resource object from the model
     *
     * @return mixed
     */
    public function delete($id = null)
    {
        $product = $this->model->find($id);
        
        if (!$product) {
            return $this->failNotFound('Product not found.');
        }

        $this->model->delete($id);
        $response = [
            'status' => 200,
            'error' => null,
            'message' => 'Product deleted successfully.'
        ];
        
        return $this->respondDeleted($response);
    }
}
