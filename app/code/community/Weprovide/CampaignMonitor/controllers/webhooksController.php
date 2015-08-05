<?php

class Weprovide_CampaignMonitor_webhooksController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        $webhookApi = Mage::getModel('campaignmonitor/api_webhooks');
        $request = $this->getRequest();
        if($request->isPost() && $request->getHeader('User-Agent') == 'CreateSend') {
            $postData = null;

            $usualPost = $request->getPost();
            if(!empty($usualPost)) {
                $postData = $usualPost;
            } else {
                $postData = $request->getRawBody();
            }

            if($postData) {
                $parsedData = $webhookApi->parseJsonWebhook($postData);
                $events = $parsedData['Events'];

                foreach($events as $event) {
                    $eventType = $event['Type'];
                    $emailAddress = $event['EmailAddress'];
                    $webhookSubscriberId = '';

                    $customFields = $event['CustomFields'];
                    if(isset($customFields)) {
                        foreach($customFields as $customField) {
                            if($customField['Key'] == 'MagentoSubscriberId') {
                                $webhookSubscriberId = $customField['Value'];
                            }
                        }
                    }

                    if(isset($eventType)) {
                        if($eventType == 'Deactivate') {
                            if(isset($emailAddress)) {
                                $subscriber = Mage::getModel('newsletter/subscriber')->loadByEmail($emailAddress);
                                if($subscriber->isSubscribed() && $subscriber->getId() == $webhookSubscriberId) {
                                    $state = $event['State'];
                                    if(isset($state)) {
                                        $subscriber->setCampaignMonitorState($state);
                                    } else {
                                        Mage::Log('State is not set', null, 'campaignmonitor.log');
                                    }
                                    $subscriber->unsubscribe();
                                } else {
                                    Mage::Log('Could not unsubscribe: ' . $emailAddress, null, 'campaignmonitor.log');
                                }
                            }
                        }
                    } else {
                        Mage::Log('Type is not set', null, 'campaignmonitor.log');
                        throw new Exception('Type is not set');
                    }

                }
            } else {
                Mage::Log('Post data is empty', null, 'campaignmonitor.log');
                throw new Exception('Post data is empty');
            }
        }
    }

//    public function registerAction()
//    {
//        $listId = Mage::getModel('campaignmonitor/setting')->getSubscribeListApiKey();
//        $webhookApi = Mage::getModel('campaignmonitor/api_webhooks');
//        $webhookApi->createWebhook($listId, 'https://e956a2ec.ngrok.io/index.php/campaignmonitor/webhooks');
//    }
//
//    public function getwebhooksAction()
//    {
//        $listId = Mage::getModel('campaignmonitor/setting')->getSubscribeListApiKey();
//        $webhookApi = Mage::getModel('campaignmonitor/api_webhooks');
//        echo '<pre>';
//        print_r($webhookApi->getWebhooks($listId));
//        echo '</pre>';
//    }
//
//    public function removewebhookAction() {
//        $listId = Mage::getModel('campaignmonitor/setting')->getSubscribeListApiKey();
//        $webhookApi = Mage::getModel('campaignmonitor/api_webhooks');
//        $webhookApi->deleteWebhook($listId,'6a4f5c91bea2323bc7252f88a56bae71');
//    }
}