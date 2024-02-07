<?php

require_once 'spider.php';

// URL do site a ser rastreado
$url = 'http://www.sintegra.fazenda.pr.gov.br/sintegra/';

// Instanciar a classe WebCrawler
$crawler = new WebCrawler($url);

// Chamar o método de busca
$result = $crawler->search();

// Exibir os resultados
print_r($result);