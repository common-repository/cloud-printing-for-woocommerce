<?php
class PrintercoOrderItem
{
    public $Category;
    public $Item;
    public $Description;
    public $Quantity;
    public $Price;
    public $Addon;

    function __construct(){

    }
}

class PrintercoOrderItemAddon{
    public $title;
    public $name;
    public $price;
}

class PrintercoOrderExtraFee
{
    public $Name;
    public $Total;

    function __construct(){

    }
}

class PrintercoOrderPostItem{
    public $category;
    public $item;
    public $item_description;
    public $item_qty;
    public $item_price;
    public $item_addon;

    function __construct($item){
        $this->category = $item->Category;
        $this->item = $item->Item;
        $this->item_description = $item->Description;
        $this->item_qty = $item->Quantity;
        $this->item_price = $item->Price;
        $this->item_addon = $item->Addon;
    }
}
