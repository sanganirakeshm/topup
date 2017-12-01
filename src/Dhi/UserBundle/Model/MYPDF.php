<?php
namespace Dhi\UserBundle\Model;

use \TCPDF;

class MYPDF extends TCPDF {
       
        //Page header
        public function Header() {
            $headerData = $this->getHeaderData();
            
            $this->SetFont('helvetica', 'B', 10);
            $this->writeHTML($headerData['string']);
    
        }
    
        // Page footer
        public function Footer() {
           
            $domaindata = $_SESSION['_sf2_attributes']['brand'];
                    
            // Position at 25 mm from bottom
            $this->SetY(-15);
            // Set font
            $this->SetFont('helvetica', 'I', 8);
    
            $brnadname = 'ExchangeVUE';
            if($domaindata['name'] !=''){
                $brnadname = $domaindata['name'];
            } 
            
            $brnaddomain = 'www.exchangevue.com';
            if($domaindata['domain'] !=''){
                $brnaddomain = $domaindata['domain'];
            } 
            $this->Cell(0, 0, $brnadname, 0, 0, 'C');
            
            $this->Ln();
//            $this->Cell(0,0,'www.dhitelecom.com', 0, false, 'C', 0, '', 0, false, 'T', 'M');
            $this->Cell(0,0,$brnaddomain, 0, false, 'C', 0, '', 0, false, 'T', 'M');
    
            // Page number
            $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
        }
    
}
