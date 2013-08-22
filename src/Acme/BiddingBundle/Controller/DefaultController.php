<?php

namespace Acme\BiddingBundle\Controller;

use Acme\BiddingBundle\Document\Bidding;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Session\Session;

class DefaultController extends Controller
{
    /**
     * @Route("/bid")
     * @Template()
     */
    public function indexAction($name="")
    {
        // get the session
    	$session = $this->getRequest()->getSession();
 
    	// get name if exists otherwise assign a random one
    	if($session->get("name")){
    		
    		$name = $session->get("name");
    	}else{
    		
    		$session->set('name', 'Guest_' . rand(1000,9999));
    		
    		$name = $session->get("name");
    	}
    	
    	
    
    	// retrieve the last 10 records order by time insert DESC
    	$bids = $this->get('doctrine_mongodb')->getManager()
    	->createQueryBuilder('AcmeBiddingBundle:Bidding')
    	->limit(10)->sort('time', 'DESC')
    	->getQuery()->execute();
    	
   
    	
    	$data = array();
    	$i = 0;
    	
    	
    	foreach ($bids as $bid){
    		
    		// get the lastest bid price
    		if($i<1){
    			$cuttent_bid_price = $bid->getPrice();	
    		}
    		
    		$data[] = array(
    				"id"=>$bid->getId(),
    				"name"=>$bid->getName(),
    				"price"=>number_format($bid->getPrice()),
    				"time"=>$bid->getTime()->format('Y-m-d h:i:s'),

    		);
    		
    		$i++;
    	}
    	 
    	asort($data);
    	
    	// set the next bid price to be plus 5
    	$next_bid_price = $cuttent_bid_price + 5;
    	
    
    	return $this->render('AcmeBiddingBundle:Default:index.html.twig',
    						array('name' => $name,'price'=>$next_bid_price,"data"=>$data)
    						);
    }
    
    
    /**
     * 
     * @param string $name
     * @param int $price
     */
    public function addBidAction($price)
    {
    	
    	$session = $this->getRequest()->getSession();

    	if($session->get("name")){
    		
    		$name = $session->get("name");
    	}else{
    		
    		// stop here
    	}
    	
    	
    	$bid = new Bidding();
    	$bid->setName($name);
    	$bid->setPrice($price);
    	$bid->setTime(new \DateTime());
    	
    	$dm = $this->get('doctrine_mongodb')->getManager();
    	$dm->persist($bid);
    	$dm->flush();
    	 
    	$bids = $this->get('doctrine_mongodb')->getManager()
    	->createQueryBuilder('AcmeBiddingBundle:Bidding')
    	->limit(10)->sort('time', 'DESC')
    	->getQuery()->execute();
    	
    	$data = array();
    	$current_bid = 0;
    	$i = 0;
    	foreach ($bids as $bidx){
    		
    		if($i<1){
    			 
    			$current_bid = $bidx->getPrice() + 5;
    			 
    		}
    		$data[] = array(
    				"id"=>$bidx->getId(),
    				"name"=>$bidx->getName(),
    				"price"=>number_format($bidx->getPrice()),
    				"time"=>$bidx->getTime()->format('Y-m-d h:i:s'),
    		);
    		
    		$i++;
    	}
    	 
    	asort($data);
    	
    	$new_data = array();
    	
    	foreach($data as $n_data){
    		
    		$new_data[] = $n_data;
    		
    	}
    	
    	
    	 
    	$host = $this->container->getParameter('rabbitmq.host');
    	$port = $this->container->getParameter('rabbitmq.port');
    	$user = $this->container->getParameter('rabbitmq.user');
    	$pass = $this->container->getParameter('rabbitmq.pass');
    	$vhost = $this->container->getParameter('rabbitmq.vhost');
    	
    	$producer = new \Thumper\Producer($host, $port, $user, $pass, $vhost);
  
    	$producer->setExchangeOptions(array('name' => 'logs-exchange', 'type' => 'topic'));
    	$producer->publish(json_encode(array("history"=>$new_data,"current_bid"=>$current_bid)));
    	
    	
    	return new Response("data");
    }
    
   
}
