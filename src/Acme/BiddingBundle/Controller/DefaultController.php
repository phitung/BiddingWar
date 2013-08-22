<?php

namespace Acme\BiddingBundle\Controller;

use Acme\BiddingBundle\Document\Bidding;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\JsonResponse;

class DefaultController extends Controller
{
	/**
	 * Default action (show all bids)
	 *
	 * @Route("/")
	 * @Template()
	 * @return array
	 */
	public function indexAction()
	{
		// get the session
		$session = $this->getRequest()->getSession();
		
		// get name if exists otherwise assign a random one
		$name = $session->get('name');
		if(!$name){
			$session->set('name', 'Guest_' . rand(1000,9999));
			$name = $session->get('name');
		}
		 
		// retrieve the last 10 records order by time insert DESC
		$bids = $this->retrieveRecentBids();
		 
		$data = array();
		$i = 0;
		foreach ($bids as $bid){

			// get the lastest bid price
			if($i<1){
				$currentBidPrice = $bid->getPrice();
			}
			$data[] = array(
							'id'=>$bid->getId(),
							'name'=>$bid->getName(),
							'price'=>number_format($bid->getPrice()),
							'time'=>$bid->getTime()->format('Y-m-d h:i:s')
							);

			$i++;
		}

		asort($data);
		 
		// set the next bid price to be plus 5
		$nextBidPrice = $currentBidPrice + 5;
		return array('name' => $name,'price'=>$nextBidPrice,'data'=>$data);
	}


	/**
	 * Add a new bid
	 *
	 * @Route(“/add-bid/{price}”)
	 * @param int $price
	 * @return JsonResponse
	 */
	public function addBidAction($price)
	{
		// get the session
		$session = $this->getRequest()->getSession();
		
		$name = $session->get('name');

		if(!$name){
			return JsonResponse(array('success' => false));
		}
		 
		// insert new bid 
		$bid = new Bid();
		$bid->setName($name);
		$bid->setPrice($price);
		$bid->setTime(new \DateTime());
		 
		$dm = $this->get('doctrine_mongodb')->getManager();
		$dm->persist($bid);
		$dm->flush();

		// retrieve the last 10 records order by time insert DESC
		$bids = $this->retrieveRecentBids();
		 
		$data = array();
		 
		$i = 0;
		foreach ($bids as $bid){

			$data[] = array(
					'id'=>$bid->getId(),
					'name'=>$bid->getName(),
					'price'=>number_format($bid->getPrice()),
					'time'=>$bid->getTime()->format('Y-m-d h:i:s'),
			);

			$i++;
		}

		asort($data);
		
		// loop one more time to reverse the bids order (newest at the bottom)
		$newData = array();
		foreach($data as $ndata){

			$newData[] = $ndata;

		}
		 
		$nextBidPrice = $price + 5;

		// get rabbitmq config values
		$host = $this->container->getParameter('rabbitmq.host');
		$port = $this->container->getParameter('rabbitmq.port');
		$user = $this->container->getParameter('rabbitmq.user');
		$pass = $this->container->getParameter('rabbitmq.pass');
		$vhost = $this->container->getParameter('rabbitmq.vhost');
		 
		// publish the bid history and next bid price to exchange
		$producer = new \Thumper\Producer($host, $port, $user, $pass, $vhost);
		$producer->setExchangeOptions(array('name' => 'logs-exchange', 'type' => 'topic'));
		$producer->publish(json_encode(array('history'=>$newData,'next_bid_price'=>$nextBidPrice)));
		 
		 
		return new JsonResponse(array('success' => true));
		 
	}
	
	/**
	 * Retrieve the 10 most recent bids
	 *
	 * @return array<Bidding>
	 * 
	 */
	 protected function retrieveRecentBids()
	 {
		 $bids = $this->get('doctrine_mongodb')->getManager()
		 				->createQueryBuilder('AcmeBiddingBundle:Bidding')
		 				->limit(10)->sort('time', 'DESC')
		 				->getQuery()->execute();
		
		 return $bids;
	 }

	 
}
