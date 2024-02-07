<?php

class WebCrawler {
    private $url;

    //Construtor
    public function __construct($url) {
        $this->url = $url;
    }

    //Função principal do Spider. Irá receber o captcha digitado, pegar a página e retornar os dados. Tambem irá verificar se a página
    //recuperada indica algum erro.
    public function search() {
        $captcha = $this->quebrarCaptcha();
        $html = $this->fetchHTML($captcha);
        if(preg_match('#NúMERO\sDE\sCONTROLE#is', utf8_encode($html))) {
            echo"\n\nCAPTCHA ERRADO\n";
            exit(1);
        } 
        
        if(preg_match('#CNPJ\sNÃO\sCADASTRADO#is', utf8_encode($html))) {
            echo"\n\nCNPJ NAO CADASTRADO\n";
            exit(1);
        }
        $data = $this->parserHTML($html);
        return $data;
    }

    //Função que faz uma requisição POST para recuperar os dados de um CNPJ. Tem como parâmetro o captcha
    private function fetchHTML($captcha) {
        //cUrl passando a URL onde será feita a requisição
        $ch = curl_init('http://www.sintegra.fazenda.pr.gov.br/sintegra/');

        //Dados da requisição POST
        $postData = array(
            '_method'=> 'POST',
            'data[Sintegra1][CodImage]'=> $captcha,
            'data[Sintegra1][Cnpj]'=> '00.063.744/0001-55',
            'empresa'=> 'Consultar Empresa',
            'data[Sintegra1][Cadicms]'=> '',
            'data[Sintegra1][CadicmsProdutor]'=> '',
            'data[Sintegra1][CnpjCpfProdutor]'=> ''
        );

        //Opções do cURL
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 1);
        //curl_setopt($ch, CURLOPT_VERBOSE, true);
        
        //Execução da requisição
        $html = curl_exec($ch);
        curl_close($ch);
        return $html;
    }

    //Função que faz uma requisição GET, pegando a imagem do captcha e, assim, permitindo que o usuário digite o captcha.
    private function quebrarCaptcha(){
        //Captcha é gerado via numero aleatorio
        $numero_aleatorio = mt_rand() / mt_getrandmax();

        //cUrl passando a URL onde será feita a requisição
        $ch = curl_init('http://www.sintegra.fazenda.pr.gov.br/sintegra/captcha?' . $numero_aleatorio);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Executa a requisição e obtém a resposta
        $response = curl_exec($ch);
        // Verifica por erros
        if (curl_errno($ch)) {
            echo 'Erro cURL: ' . curl_error($ch);
        } else {
            echo "Campo input preenchido com sucesso. Resposta do servidor:\n";
            file_put_contents('captcha.jpeg', $response);
        }
        curl_close($ch);

        //Entrada do captcha manual, onde é necessário o usuário digitar qual é o captcha da imagem
        echo 'Digite o captcha: ';
        $entrada = trim(fgets(STDIN));
        return $entrada;
    }

    //Função que monta o Array que será enviado como resposta
    private function parserHTML($html){
        //Essa função ficará em loop enquanto for encontrado o trecho "Este CNPJ possui", indicando
        //que o CNPJ informado possui mais de uma IE.
        $contador = 0;
        do {
            //Append do vetor com os dados para o vetor de resposta
            $resultado_final[$contador] = $this->getInfos($html);

            //Regex que identifica se existe o trecho "Este CNPJ possui", indicando mais IEs para o mesmo CNPJ
            preg_match_all('/Este\sCNPJ\spossui/is', $html, $outro_ie);
            if(count($outro_ie) == 0){
                break;
            }
            $contador = $contador + 1;

            //Dados da requisição post
            $postData = "_method=POST&data%5BSintegra1%5D%5BcampoAnterior%5D=iLbxzDbeVoBTlD26XLYBAKRgdeMX6wSNXqpVaMY27XAku1gFIWQyNlJG2nXscpVqzeQqOk5wj9B9OhhU%252FjccSEK7bkzEJ9DnZesNEZCG5CZF26jLHeHjfHH0ykihDqgfYeVUZBK8inpV2OQ0Ie0k7x7QZMALdu6ACCy%252BoGI3pmkD6WWfsMAlkXZNiUPTNAbv%252Fjj0vKc%252B3Koc%252FLhTmRiVV2OqYXLVPjEsnigE2YeQAm%252BxNazv7uYV30jBRY92H7k%252FjE7d4W6x5LC94%252FE1qzoh4kxvNWYcySqRYk%252BtAyctC%252BsxJ4fNI28N44%252F5pj04PqdMRS4zoXm11G5au%252F1zHueBOy0TfF7pipWEfwy4RHLeRb46uLiyim3FSY3hnmrxRR%252B52qzu4pAM4TwK8YfgrWs9o8AHYMod7PjPQwWdjYWxwcAquFzugRN81Dq2hjfKEHnzOWflfjzTL6ISrqKasBAabZE7GRJiYyAtF3VVNuKkSfuDNO27a2XbjOrE71TQsr8Ddk2JQ9M0Bu%252Bb%252FXU5MgFiCVOWkb5egDz3IUrJinwHIdcy6f0%252BdhKly9Vy34gnl9HYe4wUexlICY1ZQbAX4Mf7wVZ0IoiUg4XYJvJRLw1m2ni8z3tHhVzTC%252F%252FWtL9ekVmpE74PTWKyQsuJOg%252BZlhYAzmv%252Fl7qjmQ5GJxkHHSGP6O8OdNPagancdB6MuRqJIamu214L4tiazne8vK4DW%252B9wUk5atwTrwBgIkFOXPX7h1KHPvPEqscapIbuAJwpzoC59wE9lEumd5ZBYY4QyO2tBrZbaF3thrAIfvX%252BoDb0f8hpugyYhxYxJ3WbLeKRtryVZPZzyI1cxYvEqPns15nh56Eg8eMB2QrqnRYOOM%252BZMM921vrT%252FCF3VDHjpP4eryGSAViAttvSlgPUdxUNFXAV04xl9Mca%252BzyJtnde4iha0aPm5C9aUO4vyOy%252BesKl0RHGnGOBWbJlMsCDhP3%252BuSTFXkc9PLBOsM7iF%252FplccygMQpki0%252BpHzerqGHPiONJDxteZfbmrFbD4FTo1qM8d9dRh%252BlGNEJgxytilGFoGHy2RUkks1TKl7mTh0epFY486ND7NhI3p5TYUWivyNjsV79W0hnrDUEotQpH585hkJdEPfKo4mk5LzVMiwZb6ESU%252BxculiomKmHACh6Z88IMTIN5Djnm7CwRqfcKQ3ZZAUAbXhHVjGdDYK%252F%252Fh65a3ezFv9PANoCQlk4JnyGv%252BFzZXIVS8u3Fg7PF805FX1ouH01XR6CFf0XBhXkVc4NLwDgbr1Hq3oSAI%252F5%252FbXVjugFYCxMJ0tBK%252FpP7LNtd%252By5KdLwL14m2%252ByV7U1K7pbV1%252BCEU8X8bic%252Br5vOvPlFkt4kgb%252FPtHF8%252FEvA8fIzLzoE7sJ2iimSpEsu1If4QyIGv9GxECMNcNPOGTDwY%252FS5FvzVkmGkF3kjwJZYxwoxfeA0uQ46TxJyEYY2M88Mdi4EkLErvO7AyS0g7rMcq4%252FFUycJudoSeY5IMFG18io2NSnUrLVbacg%252FkuF7GnGt3O4Wngyd9cca89Wla%252FBdoretusMay6h2ONlQFrDZ2g9q%252B0Qsmfo2Ikjq%252FPkibAU3RCaWBz0aRug0%252BIBGfQs66Y3Ug%252FAPoHPOlMi%252B8Du8FEmVOfxkBt0RRBrgt9Wq9RlZdrAUR9PN28%252F9Sdt3B5s0Ssh01n2%252Fkqi7KbYYd5pSMtXi4leoiOtus7G%252FWAAus4V9FdvtRN9TwbXQm6yPKxpjCI%252BI4im2i%252BGc38z3RXgi%252Br23Kmus5VuhXe%252BV%252F3iKISFTScWjUsCeHF8S%252FQK2WVqENepbhg%252B5xDuzO6aQVDKjTvwAEOMo16%252BwToATCRhL3KFGkwdpVFe59ryoE%252BlUV7n2vKgT6VRXufa8qBPpVFe59ryoE%252BlUV7n2vKgT7cYVglVyIOUxpm8mW91nwt71HQ69L2N7iDg590OjdaHS0wLYzKIrtmXXJW8OqhAMVc5nOV62s78u%252FdIj4EyAi5NMDGDFXAoKWRjTN4i4KV%252FZGNM3iLgpX9kY0zeIuClf2RjTN4i4KV%252FZGNM3iLgpX9kY0zeIuClf2RjTN4i4KV%252FZGNM3iLgpX9kY0zeIuClf2RjTN4i4KV%252FZGNM3iLgpX9kY0zeIuClf2RjTN4i4KV%252FZGNM3iLgpX9kY0zeIuClf21Hq4KtI0jDASrUQvlIkxsbE%252BmN9hMSZaFHS2lU5sT%252BrFBp3B7CdyCNrGDsyu9NK3revozpKEkJoqcdJsjdj5okY0zeIuClf2RjTN4i4KV%252FZGNM3iLgpX9kY0zeIuClf2RjTN4i4KV%252FZGNM3iLgpX9kY0zeIuClf2RjTN4i4KV%252FZGNM3iLgpX9kY0zeIuClf2RjTN4i4KV%252FZGNM3iLgpX9PZUCkGWxl1z03aDmPlouUy%252Bq3MFE2E0E9JyQVEgmGDrCs9e7WW%252BtvU50qFcTOsbp3pQjp624oZcZRk8HFLyUSiyTKBLLbDUUfpVcp8U96%252B9HkzqGWqDpPP7PAMYp1Khv8W%252Bdy5aoFsFQmExrCBximRYKsFOTDuxTCZuin5HZdDovKm8WpLSpj5dZqEG36W1B8EN5S7f2Y1ZajzY%252BVtCeilSTTtV04jfJdGMsjbbXf%252Fi4iQVeT7N2%252FigRxAqvQUnL6RMkN%252BFijLnSUu2bBY9lWC2LHc9MFaGnaLHsevdaf5co1uv3QDVAGKXyOWn8IuUkXQTcaNovkWagRvlHXgno8fnCQvR31PUG802g5OfOIST0L%252BMumlKC9fVPoGZyZaroO5O57C9KNzVPWCgXRe6nY3q9V4LL%252F0gLBmL%252FhaKbK7mri6EEdMY5uqIP4h%252Fr7y0rXDJYRcB6DgegRvlHXgno8fzoRUGRyLwXx7PUM7iFD0TB3grsSQMFVliXkbEKpW3O73QaX2YfaWdz4jjSQ8bXmatGMIq8gr8o9C%252FjLppSgvUdbN3%252FEOnS5n2O%252F19bVqUInQLCL4eMRHB3t9Epj0hxnKVlzVZP7Ga9AABGPpSa%252F2LtoGHV8yugKC2pVknylh1pGUb76vYDzEPeJJVcg1tslozG5ujUD4BgToDU7fWQiA2mPWRWe%252Fy6c4YYU2IBv%252FLyg%252FiFjjHjDKWE97SaJyWpMFyOWytaLekgsLzCSEnd7i4ESGAWxFxEEmcFVCVq6KNl52hoBvEKHtsNn7ypziPVO%252FLnuqJRiRcBlUV7n2vKgT6VRXufa8qBPpVFe59ryoE%252BlUV7n2vKgT6VRXufa8qBPpVFe59ryoE%252BlUV7n2vKgT6VRXufa8qBPpVFe59ryoE%252BlUV7n2vKgT6VRXufa8qBPpVFe59ryoE%252BlUV7n2vKgT6VRXufa8qBPpVFe59ryoE%252BlUV7n2vKgT6VRXufa8qBPpVFe59ryoE%252BlUV7n2vKgT6VRXufa8qBPpVFe59ryoE%252BlUV7n2vKgT6VRXufa8qBPpVFe59ryoE%252BlUV7n2vKgT6VRXufa8qBPpVFe59ryoE%252BlUV7n2vKgT6VRXufa8qBPpVFe59ryoE%252BlUV7n2vKgT6VRXufa8qBPpVFe59ryoE%252BlUV7n2vKgT6VRXufa8qBPpVFe59ryoE%252BlUV7n2vKgT6VRXufa8qBPpVFe59ryoE%252BlUV7n2vKgT6VRXufa8qBPpVFe59ryoE%252BlUV7n2vKgT6VRXufa8qBPpVFe59ryoE%252BlUV7n2vKgT6VRXufa8qBPpVFe59ryoE%252BlUV7n2vKgT6VRXufa8qBPpVFe59ryoE%252BlUV7n2vKgT6VRXufa8qBPpVFe59ryoE%252BlUV7n2vKgT6VRXufa8qBPpVFe59ryoE%252BlUV7n2vKgT6VRXufa8qBPpVFe59ryoE%252BlUV7n2vKgT6VRXufa8qBPpVFe59ryoE%252BlUV7n2vKgT6VRXufa8qBPpVFe59ryoE%252BlUV7n2vKgT6VRXufa8qBPpVFe59ryoE%252BlUV7n2vKgT6VRXufa8qBPpVFe59ryoE%252BlUV7n2vKgT6VRXufa8qBPpVFe59ryoE%252BlUV7n2vKgT6VRXufa8qBPpVFe59ryoE%252BlUV7n2vKgT6VRXufa8qBPpVFe59ryoE%252BlUV7n2vKgT6VRXufa8qBPi0wLYzKIrtmXXJW8OqhAMVc5nOV62s78u%252FdIj4EyAi5NMDGDFXAoKUJ8%252FgK9E7ml22zp9cPSlV%252FrKgM5A3FVBL1R07FhGWSshMrfod68rcluWcfOgs8M3Cyc8eCYz0QLhyHhQza9YhM%252FPZGRmN5zg3IvZkFduza2T8A%252Bgc86UyL7wO7wUSZU58OJv%252FBfQHmTMMMENX9zXyCFDHoWSzj%252BpWRjTN4i4KV%252FZGNM3iLgpX9kY0zeIuClf2RjTN4i4KV%252FZGNM3iLgpX9kY0zeIuClf2RjTN4i4KV%252FZGNM3iLgpX9kY0zeIuClf2RjTN4i4KV%252FZGNM3iLgpX9kY0zeIuClf2RjTN4i4KV%252FZGNM3iLgpX9yWFreQQrdfbSbsvhJrxczBy6UDt8CahbBcCsFeAPKaQEq1EL5SJMbGxPpjfYTEmWhR0tpVObE%252FqxQadwewncgjaxg7MrvTSt63r6M6ShJCaKnHSbI3Y%252BaJGNM3iLgpX9kY0zeIuClf2RjTN4i4KV%252FZGNM3iLgpX9kY0zeIuClf2RjTN4i4KV%252FZGNM3iLgpX9kY0zeIuClf2RjTN4i4KV%252FZGNM3iLgpX9kY0zeIuClf2RjTN4i4KV%252FYzgeb2Qo%252BAWEjGzssgHk5vIvZkFduza2T8A%252Bgc86UyL7wO7wUSZU5%252BcwK1aVNyErZiyeE2Nxr%252FJkY0zeIuClf2RjTN4i4KV%252FZGNM3iLgpX9kY0zeIuClf2RjTN4i4KV%252FZGNM3iLgpX9kY0zeIuClf2RjTN4i4KV%252FZGNM3iLgpX9kY0zeIuClf2RjTN4i4KV%252FZGNM3iLgpX9kY0zeIuClf2RjTN4i4KV%252FZGNM3iLgpX9yWFreQQrdfaOa2GVOoYocQ%253D%253D&consultar=";
            //cURL para a requisição que tem como intuito pegar os próximos dados
            $ch = curl_init("http://www.sintegra.fazenda.pr.gov.br/sintegra/sintegra1/consultar");
            //cURL options
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            $html = curl_exec($ch);
            curl_close($ch);
        } while (count($outro_ie) != 0);
        return $resultado_final;
    }
    //Função que pega os dados da página
    private function getInfos($html){
        //Regex para recuperar cada atributo necessario.
        //Recupera CNPJ, IE, Numero do endereco, CEP, UF, Informacoes das situacao atual e as atividades principais
        preg_match_all('/class="form_conteudo">([^<]*)</is', $html, $cnpj_ie);
        //Recupera a Razao Social, complemento do endereco, municipio e telefone
        preg_match_all('/class="form_conteudo"\scolspan="3">([^<]*)</is', $html, $razao_social);
        //Recupera Logradouro, municipio e o email
        preg_match_all('/class="form_conteudo"\scolspan="5">([^<]*)</is', $html, $logradouro);
        //Recupera a data de acesso realizada
        preg_match_all('/(\d{2}\/\d{2}\/\d{4}) - (\d{2}:\d{2}:\d{2})/is', $html, $data_hora);

        //Todas as informacoes sao armazenadas em um array e retornadas
        $retorno = array(
            'cnpj'=> $cnpj_ie[1][0],
            'ie'=> $cnpj_ie[1][1],
            'razao_social'=> $razao_social[1][0],
            'logradouro'=> $logradouro[1][0],
            'numero'=> $cnpj_ie[1][2],
            'complemento'=> $razao_social[1][1],
            'bairro'=> $logradouro[1][1],
            'cep'=> $cnpj_ie[1][4],
            'municipio'=> $razao_social[1][2],
            'uf'=> $cnpj_ie[1][3],
            'telefone'=> $razao_social[1][3],
            'email'=> $logradouro[1][2],
            'data_inicio'=> trim($cnpj_ie[1][7]),
            'situacao_atual'=> explode(" - DESDE ", trim($cnpj_ie[1][8]))[0],
            'data_situacao_atual'=> explode(" - DESDE ", trim($cnpj_ie[1][8]))[1],
            'data'=> $data_hora[1][0],
            'hora'=> $data_hora[2][0],
            'atividade_principal'=> array(
                'codigo'=> explode(" - ", $cnpj_ie[1][5])[0],
                'descricao'=> explode(" - ", $cnpj_ie[1][5])[1]
            )
        );
        return $retorno;
    }
}
