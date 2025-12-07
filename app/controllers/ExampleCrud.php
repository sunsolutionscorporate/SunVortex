<?php
class ExampleCrud extends Controller
{
   public function index()
   {
      $model = $this->loadModel('example/Example_model');
      $items = $model->getAll();
      return $this->view('example/index', ['items' => $items]);
   }

   public function create()
   {
      return $this->view('example/form');
   }

   public function store()
   {
      $name = $this->request->post('name');
      $email = $this->request->post('email');
      $model = $this->loadModel('example/Example_model');
      $model->insert(['name' => $name, 'email' => $email]);
      $this->redirect('/examplecrud');
   }

   public function edit($id)
   {
      $model = $this->loadModel('example/Example_model');
      $item = $model->find($id);
      return $this->view('example/form', ['item' => $item]);
   }

   public function update($id)
   {
      $name = $this->request->post('name');
      $email = $this->request->post('email');
      $model = $this->loadModel('example/Example_model');
      $model->update($id, ['name' => $name, 'email' => $email]);
      $this->redirect('/examplecrud');
   }

   public function delete($id)
   {
      $model = $this->loadModel('example/Example_model');
      $model->delete($id);
      $this->redirect('/examplecrud');
   }
}
