<?php

class Printer
{
    const W1 = 4;

    const W2 = 8;

    const W3 = 13;

    const W4 = 18;

    const W5 = 23;

    const W6 = 28;

    const W7 = 33;

    const W8 = 38;

    const W9 = 43;

    const W10 = 48;

    const DEFAULT_VALUE = "-1";

    const ALIGN_RIGHT = "2";

    const ALIGN_LEFT = "0";

    const ALIGN_CENTER = "1";

    const UNDERLINE_NONE = "0";

    const UNDERLINE_NORMAL = "1";

    const UNDERLINE_BOLD = "2";


    /**
     * 重新设置打印机打印模式为上电时的默认设置，清空缓冲区
     */
    const RESET = "\x1B\x40";

    /**
     * 启用打印机
     */
    const ENABLE = "\x1B\x3D1";

    /**
     * 禁用打印机
     */
    const DISABLE = "\x1B\x3D0";

    /**
     * 设置行间距
     */
    const LINE_MARGIN = "\x1B\x33";

    const CUSTOM_BEGIN = "\x1B\x251";

    const CUSTOM_END = "\x1B\x250";
    /**
     * 将行间距设置为默认值
     */
    const DEFAULT_LINE_MARGIN = "\x1B\x32";

    /**
     * 设置横向和纵向移动单位
     * @param x y
     * 最终值为25.4/x   25.4/y
     * 有很多东西都依赖它
     */
    const SET_SHIFT_UNIT = "\x1D\x50";

    /**
     * 设置字体大小为宽高各一倍
     */
    const FONT_SIZE = "\x1D\x21";

    /**
     * 左对齐
     */
    const ALIGN = "\x1B\x61";

    /**
     * 粗体字
     */
    const FONT_BOLD = "\x1B\x21\x08";

    /**
     * 结束粗体字
     */
    const FONT_UNBOLD = "\x1B\x21\x00";

    /**
     * 切换到页模式
     */
    const PAGE_MODE = "\x1B\x4C";

    /**
     * 切换到标准模式
     */
    const STANDARD_MODE = "\x1B\x53";

    /**
     * 标准模式下打印
     */
    const STANDARD_MODE_PRINT = "\x0A";

    /**
     * 设置或者取消下划线
     */
    const UNDERLINE = "\x1B\x2D";

    const CUT = "\x1D\x56";

    /**
     * 页模式下打印并回到标准模式
     */
    const PAGE_MODE_PRINT = "\x1B\x0C";

    private $funcs = [
        'fontSize' => self::FONT_SIZE, // 参数0x11,22,33,44,55,66,77,00...宽高倍数
        'fontBold' => self::FONT_BOLD,
        'fontUnbold' => self::FONT_UNBOLD, 
        'align' => self::ALIGN,        // 参数 0,1,2 分别设置对齐方式
        'underline' => self::UNDERLINE, // 参数 0,1,2 分别设置下划线宽度
        'lineMargin' => self::LINE_MARGIN,   // 0 <= n <= 255
        'cut' => self::CUT,
        'customBegin' => self::CUSTOM_BEGIN,
        'customEnd' => self::CUSTOM_END
    ];

    private $handler;

    public function __construct($ip, $port)
    {
        $this->handler = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (!socket_set_block($this->handler)) {
            throw new \Exception ('设置阻塞模式失败:'.socket_strerror(socket_last_error()));
        }
        if (!socket_connect($this->handler, $ip, $port)) {
            throw new \Exception ('连接打印机失败'.socket_strerror(socket_last_error()));
        }
        $this->reset();
    }

    public function __call($method, array $args = null)
    {
        if ($method == 'fontSize' && $args == null) {
            $args = ["\x00"];
        }
        if ($method == 'underline' && $args == null) {
            $args = [self::UNDERLINE_NORMAL];
        }
        if ($method == 'lineMargin' && $args == null) {
            return $this->write(self::DEFAULT_LINE_MARGIN);
        }
        if ($method == 'cut' && $args == null) {
            $args = ["0"];
        }
        if (!isset($this->funcs[$method])) {
            throw new \Exception('没有方法：'.$method);
        }
        $cmd = $this->funcs[$method] . implode('', $args ?? []);
        return $this->write($cmd);
    }

    /**
     * 设置页模式
     */
    public function setPageMode()
    {
        $this->write(self::PAGE_MODE);
        $this->mode = self::PAGE_MODE;
        return $this;
    }

    /**
     * 设置标准模式
     */
    public function setStandardMode()
    {
        $this->write(self::STANDARD_MODE);
        $this->mode = self::STANDARD_MODE;
        return $this;
    }

    public function line()
    {
        $this->lineMargin(10);
        $this->write("________________________________________________");
        $this->print();
        $this->lineMargin();
        return $this;
    }

    /**
     * 向打印机发送数据
     */
    public function write($content)
    {
        socket_write($this->handler, $content, strlen($content));
        return $this;
    }

    /**
     * 打印
     */
    public function print($reset = true)
    {
        if ($this->mode == self::PAGE_MODE) {
            if ($reset) {
                $this->reset();
            }
            return $this->write(self::PAGE_MODE_PRINT);
        }
        if ($reset) {
            $this->reset();
        }
        return $this->write(self::STANDARD_MODE_PRINT);
    }

    /**
     * 重置打印机状态
     */
    public function reset()
    {
        $this->setStandardMode();
        $this->fontSize("\x00");
        $this->align(self::ALIGN_LEFT);
    }

    public function vs($str)
    {
        return iconv("UTF-8", "GBK", $str);
    }

    public function sr($length, $str)
    {
        return sprintf("%-".$length."s", $this->vs($str));
    }

    public function sl($length, $str)
    {
        return sprintf("%-".$length."s", $this->vs($str));
    }

    public function __destruct()
    {
        socket_close($this->handler);
    }
}
