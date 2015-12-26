<?php
/**
 * PHPExcel
 * @author Tang
 */
/** Include PHPExcel */
require_once dirname(__FILE__) . '/Classes/PHPExcel.php';
class phpExcelDemo{
    private $objPHPExcel;
    private $data;
    private $name="test";
    public function __construct($list){
        if(!$list){
            die('数据不能为空');
        }
        $this->data=$list;
        /** Error reporting */
        error_reporting(E_ALL);
        ini_set('display_errors', TRUE);
        ini_set('display_startup_errors', TRUE);
        date_default_timezone_set('Europe/London');

        if (PHP_SAPI == 'cli')
            die('This example should only be run from a Web Browser');

        // Create new PHPExcel object
        $this->objPHPExcel = new PHPExcel();
        $this->done();
    }
    private function done(){
        // Set document properties
        $this->objPHPExcel->getProperties()->setCreator("Maarten Balliauw")
            ->setLastModifiedBy("Maarten Balliauw")
            ->setTitle("Office 2007 XLSX Test Document")
            ->setSubject("Office 2007 XLSX Test Document")
            ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
            ->setKeywords("office 2007 openxml php")
            ->setCategory("Test result file");
    }
    public function Add(){
        // Add some data
        $this->objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', iconv('gb2312', 'utf-8', '编号'))
            ->setCellValue('B1', iconv('gb2312', 'utf-8', '金币'))
            ->setCellValue('C1', iconv('gb2312', 'utf-8', '金钱'))
            ->setCellValue('D1', iconv('gb2312', 'utf-8', '支付宝账号'));
        foreach($this->data as $key=>$v){
            $i=$key+2;
            $this->objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue("A$i", $v['id'])
                ->setCellValue("B$i", $v['coin'])
                ->setCellValue("C$i", $v['money'])
                ->setCellValue("D$i", $v['account']);
        }
        // Rename worksheet
        $this->objPHPExcel->getActiveSheet()->setTitle('test');
        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $this->objPHPExcel->setActiveSheetIndex(0);
    }
    public function setName($name){
        if($name){
            $this->name=$name;
        }
    }
    public function download(){
    // Redirect output to a client’s web browser (Excel5)
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$this->name.'.xls"');
        header('Cache-Control: max-age=0');
    // If you're serving to IE 9, then the following may be needed
        header('Cache-Control: max-age=1');

    // If you're serving to IE over SSL, then the following may be needed
        header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
        header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header ('Pragma: public'); // HTTP/1.0

        $objWriter = PHPExcel_IOFactory::createWriter($this->objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }
}
