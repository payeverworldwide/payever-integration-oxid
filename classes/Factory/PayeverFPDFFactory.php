<?php

class PayeverFPDFFactory
{
    /**
     * @return FPDF
     */
    public function create()
    {
        return new FPDF();
    }
}
