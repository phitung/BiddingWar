<?php

namespace Acme\StoreBundle\Controller;

use Acme\StoreBundle\Document\Product;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class DefaultController extends Controller
{
    /**
     * @Route("/hello/{name}")
     * @Template()
     */
    public function indexAction($name)
    {
        return array('name' => $name);
    }
    
    public function createAction()
    {
    	$product = new Product();
    	$product->setName('A Foo Bar' . rand(5,300));
    	$product->setPrice(rand(10,9999));
    
    	$dm = $this->get('doctrine_mongodb')->getManager();
    	$dm->persist($product);
    	$dm->flush();
    	
    	$products = $this->get('doctrine_mongodb')->getManager()->getRepository('AcmeStoreBundle:Product')->limit(10)->sort('id', 'DESC')->getQuery()->execute();
    	
    	
    	
    	$producer = new Thumper\Producer(HOST, PORT, USER, PASS, VHOST);
    	$producer->setExchangeOptions(array('name' => 'hello-exchange', 'type' => 'direct'));
    	$producer->publish(json_encode(array('foo' => 'bar')));
    
    	return new Response('Created product id '.$product->getId());
    }
    
    public function showAction($id)
    {
    	$product = $this->get('doctrine_mongodb')
    	->getRepository('AcmeStoreBundle:Product')
    	->find($id);
    
    	if (!$product) {
    		throw $this->createNotFoundException('No product found for id '.$id);
    	}
    	
    	return new Response('Created product id '.$product->getName());
    
    	// do something, like pass the $product object into a template
    }
}
