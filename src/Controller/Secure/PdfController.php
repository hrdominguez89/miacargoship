<?php

namespace App\Controller\Secure;

use App\Repository\S10CodeRepository;
use App\Repository\DispatchRepository;
use App\Repository\BagsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Snappy\Pdf;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Proxies\__CG__\App\Entity\Bags;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/pdf')]
class PdfController extends AbstractController
{
    private $pdf;
    public function __construct(Pdf $pdf)
    {
        $this->pdf = $pdf;
    }
    #[Route('/cn22/{s10code_id}', name: 'app_pdf_cn22')]
    public function index($s10code_id, S10CodeRepository $s10CodeRepository,EntityManagerInterface $em): Response
    {

        $data['s10code'] = $s10CodeRepository->find($s10code_id);
        if ($data['s10code']) {
            if (!$data['s10code']->getNumbercode()) {
                $data['s10code']->setNumbercode($this->generateCode($s10code_id, $s10CodeRepository));
                $data['s10code']->setCode($data['s10code']->getFormattedNumbercode());
                $em->persist($data['s10code']);
                $em->flush($data['s10code']);
            }
            $this->generateBarcodeImage($data['s10code'],'s10', $em);
        } else {
            return $this->redirectToRoute('app_secure_upu');
        }

        $html = $this->renderView('pdf/cn22.html.twig',$data);
        $pdfContent = $this->pdf->getOutputFromHtml($html);
        return new Response($pdfContent, 200, ['Content-Type' => 'application/pdf']);
    }

    private function generateCode($id, S10CodeRepository $s10CodeRepository): string
    {
        $numbers = str_pad($s10CodeRepository->countRecordsByServiceCodeAndCountryBeforeOrEqualToId($id), 8, '0', STR_PAD_LEFT);

        $secuencia = [8, 6, 4, 2, 3, 5, 9, 7];  //numeros que indica la UPU para hacer el calculo

        $suma_total = 0;
        for ($i = 0; $i < 8; $i++) {
            $suma_total += $numbers[$i] * $secuencia[$i];
        }

        $resto = $suma_total % 11; //11 numero que indica la upu para hacer el calculo

        $digito_de_seguridad = 11 - $resto; //11- resto cuenta que indica la upu para hacer el calculo

        //si es 11 el digito de seguridad es 5 si es 10 el digito de seguridad es 0 segun la UPU
        if ($digito_de_seguridad > 10) {
            $digito_de_seguridad = 5;
        }
        if ($digito_de_seguridad == 10) {
            $digito_de_seguridad = 0;
        }
        return $numbers . $digito_de_seguridad;
    }

    private function generateBarcodeImage($ObjetoEntidad,$path, EntityManagerInterface $em): void
    {
        if($path == 'bags'){
            $codigo = $ObjetoEntidad->generateBagCode();
        }else if ($path =='s10'){
            $codigo = $ObjetoEntidad->getFormattedNumbercode();
        }else{
            $codigo = $ObjetoEntidad->getDispatchCode();
        }
        $rutaArchivo = $this->getParameter('kernel.project_dir') . '/public/barcodes/'.$path.'/' . $codigo . '.png';

        // Generar código de barras con picqer/php-barcode-generator (Code128)
        $generator = new BarcodeGeneratorPNG();
        $codigoBarras = $generator->getBarcode($codigo, $generator::TYPE_CODE_128);

        // Guardar la imagen en la carpeta /public/barcodes/s10/
        file_put_contents($rutaArchivo, $codigoBarras);

        // Actualizar la entidad ObjetoEntidad para guardar la ruta de la imagen
        $rutaRelativa = '/barcodes/'.$path.'/' . $codigo . '.png';
        $ObjetoEntidad->setBarcodeImage($rutaRelativa);

        // Persistir los cambios
        $em->persist($ObjetoEntidad);
        $em->flush();
    }

    #[Route('/cn23/{s10code_id}', name: 'app_pdf_cn23')]
    public function cn23($s10code_id, S10CodeRepository $s10CodeRepository, EntityManagerInterface $em): Response{
         // Ruta absoluta de la imagen en el servidor
        $imagePath = $this->getParameter('kernel.project_dir') . '/public/images/logoems.png';
        $imagePathCode = $this->getParameter('kernel.project_dir') . '/public/images/code.png';
        
        // Convertir la imagen a base64
        if (file_exists($imagePath)) {
            $data['base64Image'] = base64_encode(file_get_contents($imagePath));
        } else {
            $data['base64Image'] = null; // Manejo de errores si la imagen no se encuentra
        }
        if (file_exists($imagePathCode)) {
            $base64ImageCode = base64_encode(file_get_contents($imagePathCode));
        } else {
            $base64ImageCode = null; // Manejo de errores si la imagen no se encuentra
        }

        $data['s10code'] = $s10CodeRepository->find($s10code_id);
        if ($data['s10code']) {
            if (!$data['s10code']->getNumbercode()) {
                $data['s10code']->setNumbercode($this->generateCode($s10code_id, $s10CodeRepository));
                $data['s10code']->setCode($data['s10code']->getFormattedNumbercode());
                $em->persist($data['s10code']);
                $em->flush($data['s10code']);
            }
            $this->generateBarcodeImage($data['s10code'],'s10', $em);
        } else {
            return $this->redirectToRoute('app_secure_upu');
        }
      
        $html = $this->renderView('pdf/cn23.html.twig',$data);        
        $options = [
            'orientation' => 'Landscape',
            'margin-top'=> '20mm',
            'margin-right' => '30mm',
            'margin-bottom' => '10mm',
            'margin-left' => '20mm',
        ];
        $pdfContent = $this->pdf->getOutputFromHtml($html, $options);
       
        return new Response($pdfContent, 200, ['Content-Type' => 'application/pdf']);
    }
    #[Route('/cn31/{dispatch_id}', name: 'app_pdf_cn31')]
    public function cn31($dispatch_id, DispatchRepository $dispatchRepository, EntityManagerInterface $em): Response{
         // Ruta absoluta de la imagen en el servidor
        $imagePath = $this->getParameter('kernel.project_dir') . '/public/images/logoems.png';
        $imagePathCode = $this->getParameter('kernel.project_dir') . '/public/images/code.png';
        
        // Convertir la imagen a base64
        if (file_exists($imagePath)) {
            $data['base64Image'] = base64_encode(file_get_contents($imagePath));
        } else {
            $data['base64Image'] = null; // Manejo de errores si la imagen no se encuentra
        }
        if (file_exists($imagePathCode)) {
            $base64ImageCode = base64_encode(file_get_contents($imagePathCode));
        } else {
            $base64ImageCode = null; // Manejo de errores si la imagen no se encuentra
        }

         $data['dispatch'] = $dispatchRepository->find($dispatch_id);
         if(!$data['dispatch']->getBarcodeImage()){
            $this->generateBarcodeImage($data['dispatch'],'dispatches', $em);
        }
        /* if ($data['dispatch']) {
            if (!$data['dispatch']->getNumbercode()) {
                $data['dispatch']->setNumbercode($this->generateCode($dispatch_id, $dispatchRepository));
                $em->persist($data['dispatch']);
                $em->flush($data['dispatch']);
            }
            $this->generateBarcodeImage($data['dispatch'], $em);
        } else {
            return $this->redirectToRoute('app_secure_upu');
        } */
        
        $html = $this->renderView('pdf/cn31.html.twig', $data);        
        $options = [
            'margin-top'=> '20mm',
            'margin-right' => '10mm',
            'margin-bottom' => '10mm',
            'margin-left' => '30mm',
               
        ];
        $pdfContent = $this->pdf->getOutputFromHtml($html , $options);
       
        return new Response($pdfContent, 200, ['Content-Type' => 'application/pdf']);
    }
    #[Route('/cn38/{bag_id}', name: 'app_pdf_cn38')]
    public function cn38($bag_id, BagsRepository $bagRepository, DispatchRepository $dispatchRepository, EntityManagerInterface $em): Response{
       
        $data['bag'] = $bagRepository->find($bag_id);
        $html = $this->renderView('pdf/cn38.html.twig', $data);        
        $options = [
            'margin-top'=> '20mm',
            'margin-right' => '10mm',
            'margin-bottom' => '10mm',
            'margin-left' => '20mm',
               
        ];
        $pdfContent = $this->pdf->getOutputFromHtml($html , $options);
       
        return new Response($pdfContent, 200, ['Content-Type' => 'application/pdf']);
    }
    #[Route('/cn33/{dispatch_id}', name: 'app_pdf_cn33')]
    public function cn33($dispatch_id, DispatchRepository $dispatchRepository, EntityManagerInterface $em): Response{
       
        /* $data['dispatch']= $dispatchRepository->find($dispatch_id);
        $bags = $data['dispatch']->getBags(); */
        $data['dispatch'] = $dispatchRepository->findWithBags($dispatch_id);
        $data['bags']  = $data['dispatch']->getBags(); 

        
        
        
       
        $html = $this->renderView('pdf/cn33.html.twig', parameters:$data );        
        $options = [
            'margin-top'=> '20mm',
            'margin-right' => '10mm',
            'margin-bottom' => '10mm',
            'margin-left' => '20mm',
               
        ];
        $pdfContent = $this->pdf->getOutputFromHtml($html , $options);
       
        return new Response($pdfContent, 200, ['Content-Type' => 'application/pdf']);
    }
    #[Route('/cn35/{bag_id}', name: 'app_pdf_cn35')]
    public function cn35($bag_id, BagsRepository $bagRepository, DispatchRepository $dispatchRepository, EntityManagerInterface $em): Response{
         // Ruta absoluta de la imagen en el servidor
        $imagePath = $this->getParameter('kernel.project_dir') . '/public/images/logoems.png';
        $imagePathCode = $this->getParameter('kernel.project_dir') . '/public/images/code.png';
        
        // Convertir la imagen a base64
        if (file_exists($imagePath)) {
            $data['base64Image'] = base64_encode(file_get_contents($imagePath));
        } else {
            $data['base64Image'] = null; // Manejo de errores si la imagen no se encuentra
        }
        if (file_exists($imagePathCode)) {
            $base64ImageCode = base64_encode(file_get_contents($imagePathCode));
        } else {
            $base64ImageCode = null; // Manejo de errores si la imagen no se encuentra
        }

        $data['bag'] = $bagRepository->find($bag_id);
        if(!$data['bag']->getBarcodeImage()){
            $this->generateBarcodeImage($data['bag'],'bags', $em);
        }
        $data['dispatch'] = $data['bag']->getDispatch();
        


        /* if ($data['bag']) {
            if (!$data['bag']->getNumbercode()) {
                $data['bag']->setNumbercode($this->generateCode($bag_id, $bagRepository));
                $em->persist($data['bag']);
                $em->flush($data['bag']);
            }
            $this->generateBarcodeImage($data['bag'], $em);
        } else {
            return $this->redirectToRoute('app_secure_upu');
        } */
        
        $html = $this->renderView('pdf/cn35.html.twig', $data );        
        $options = [
            'margin-top'=> '20mm',
            'margin-right' => '10mm',
            'margin-bottom' => '10mm',
            'margin-left' => '20mm',
               
        ];
        $pdfContent = $this->pdf->getOutputFromHtml($html , $options);
       
        return new Response($pdfContent, 200, ['Content-Type' => 'application/pdf']);
    }

}
