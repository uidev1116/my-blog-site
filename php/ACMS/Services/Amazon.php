<?php

class ACMS_Services_Amazon extends ACMS_Services 
{
    private $accessKeyId        = null;
    private $secretAccessKey    = null;
    private $associateTag       = null;
    private $baseUrl            = 'http://ecs.amazonaws.jp/onca/xml';
    private $apiVersion         = '2013-08-01';

    function __construct($tracking_id, $access_key, $secret_key)
    {
        $this->associateTag     = $tracking_id;
        $this->accessKeyId      = $access_key;
        $this->secretAccessKey  = $secret_key;
    }

    /**
     * amazon 商品詳細ページのURLからASINを取得
     *
     * @param string $url
     * @return string
     */
    function getAsinFromUrl($url)
    {
        if ( preg_match('/^http(.*)amazon(.*)\/([A-Z0-9]{10})(.*)$/', $url, $matches) ) {
            return $matches[3];
        }
        return false;
    }

    /**
     * 検証
     *
     * @return $boolean
     */
    function isValid()
    {
        if ( 0
            || empty($this->accessKeyId)
            || empty($this->secretAccessKey)
            || empty($this->associateTag)
        ) {
            return false;
        }
        return true;
    }

    /**
     * 商品情報の取得
     *
     * @param string $id
     * @return array
     */
    function amazonItemLookup($id)
    {
        $params = $this->getRequestParameter($id);
        $uri    = $this->getRequestURI($params);
        $xml    = new SimpleXMLElement($uri, NULL, true);

        return $xml;
    }

    /**
     * XMLの検証
     *
     * @param string $uri
     * @return boolean
     */
    function validXML($uri)
    {
        if ( empty($uri) ) {
            return false;
        }

        $xml = new XMLReader();
        $xml->open($uri);
        $xml->setParserProperty(XMLReader::VALIDATE, true);
        if ( !$xml->isValid() ) {
            $xml->close();
            return false;
        }
        return true;
    }

    /**
     * リクエストパラメータを組み立て
     *
     * @param string $id
     * @return array
     */
    function getRequestParameter($id)
    {
        $params = array(
            'Service'           => 'AWSECommerceService',
            'AssociateTag'      => $this->associateTag,
            'AWSAccessKeyId'    => $this->accessKeyId,
            'Operation'         => 'ItemLookup',
            'Version'           => $this->apiVersion,
            'ItemId'            => $id,
            'ResponseGroup'     => 'ItemAttributes,Images,OfferSummary',
            'Timestamp'         => gmdate('Y-m-d\TH:i:s\Z', REQUEST_TIME),
        );
        ksort($params);

        return $params;
    }

    /**
     * リクエストURIを取得
     *
     * @param array $param
     * @return string
     */
    function getRequestURI($params)
    {
        $str = '';
        foreach ( $params as $key => $val ) {
            $str .= '&'.$this->urlencodeRfc3986($key).'='.$this->urlencodeRfc3986($val);
        }
        $str    = substr($str, 1);
        $uri    = parse_url($this->baseUrl);

        $sign   = "GET\n".$uri['host']."\n".$uri['path']."\n".$str;
        $signature = base64_encode(hash_hmac('sha256', $sign, $this->secretAccessKey, true));

        return $this->baseUrl.'?'.$str.'&Signature='.$this->urlencodeRfc3986($signature);
    }

    /**
     * RFC3986エンコードを行う
     *
     * @param string $str
     * @return string
     */
    function urlencodeRfc3986($str)
    {
        return str_replace('%7E', '~', rawurlencode($str));
    }
}
