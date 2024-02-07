<?php

require_once 'spider.php';

// URL do site a ser rastreado
$url = 'http://www.sintegra.fazenda.pr.gov.br/sintegra/';

// Instanciar a classe WebCrawler
$crawler = new WebCrawler($url);

echo 'Digite o cnpj: ';
$cnpj = trim(fgets(STDIN));

        
$cnpjFormatado = substr($cnpj, 0, 2) . '.' . substr($cnpj, 2, 3) . '.' . substr($cnpj, 5, 3) . '/' . substr($cnpj, 8, 4) . '-' . substr($cnpj, 12, 2);
// Chamar o método de busca
try{
    $result = $crawler->search($cnpjFormatado);
    print_r($result);
}
catch(\Exception $exp){
    print_r($exp->getMessage());
}
