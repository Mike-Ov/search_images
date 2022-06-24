<?php

namespace App\Controller;


use GuzzleHttp\Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ImageController extends AbstractController
{
    /**
     * @param Request $request
     * @return Response
     */
    #[Route('/image', name: 'image')]
    public function index(Request $request): Response
    {
        if($request->getMethod()==='GET') {
            return $this->render('image/index.html.twig',);
        } else {
            $url = $request->request->get('url');
            $client = new Client();
            try {
                $resource = $client->request('GET', $url);
            } catch (\Exception $e){
                $errors[]=['url' => $url, 'error' => $e->getMessage()];
                return $this->render('image/index.html.twig', ['errors' => $errors]);
            }
            if($resource->getStatusCode()) {
                $text = (string)$resource->getBody();
                preg_match_all("/<\s*img\s*src\s*=\s*[\"']{0,1}([^\"'>]*)/im", $text, $imageSources);
                if(count($imageSources)){
                    $images=[];
                    $errors=[];
                    $filesSizeTotal = 0;
                    foreach ($imageSources[1] as $imageURL){
                        if($imageURL[0]==='/') {
                            $imageURL = $url . $imageURL;
                        }
                        try {
                            $data = (string)$client->request('GET', $imageURL)->getBody();
                            preg_match("/.*[.]([^\/][a-zA-Z]+)\??[^\/]*$/", $imageURL, $formatImage);
                            if($formatImage[1]) {
                                if($formatImage[1] === 'svg') {
                                    $formatImage[1] = 'svg+xml';
                                }
                                $base64 = 'data:image/' . $formatImage[1].  ';base64,' . base64_encode($data);
                                $fileSize = strlen($data);
                                $filesSizeTotal += $fileSize;
                                $images[] = [
                                    'url' => $imageURL,
                                    'data' => $base64,
                                    'size' => $fileSize
                                ];
                            }
                        } catch (\Exception $e){
                            $errors[]=['url' => $imageURL, 'error' => $e->getMessage()];
                        }
                    }
                }
                return $this->render('image/index.html.twig', ['images' => $images, 'errors' => $errors, 'filesSizeTotal' => $filesSizeTotal]);
            }
        }
    }
}
