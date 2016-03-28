<?php

class Template {
    private $template = "";
    private $content = null;
    private $data = array();
    private $stack = array();
    
    public function __construct($file, $layout = null) {
        if (!is_null($layout)) {
            $this->template = @file_get_contents($layout, true);
            $this->content = new Template($file);
        } else {
            $this->template = @file_get_contents($file, true);
        }
        
        if (!$this->template) {
            throw new InvalidArgumentException('File not found');
        }
    }
    
    private function importFile($file) {
        return @file_get_contents($file, true);
    }
    
    private function showVariable($name, $sanitize = false) {
        if (isset($this->data[$name])) {
            if ($sanitize) {
                echo htmlentities($this->data[$name]);
            } else {
                echo $this->data[$name]; 
            }
        } else {
            echo '{' . $name . '}';
        }
    }
    
    private function wrap($element) {
        $this->stack[] = $this->data;
        foreach ($element as $k => $v) {
            $this->data[$k] = $v;
        }
    }
    
    private function unwrap() {
        $this->data = array_pop($this->stack);
    }
    
    private function run() {
        ob_start();
        eval(func_get_arg(0));
        return ob_get_clean();
    }
    
    public function process($data = array()) {
        $this->data = $data;
        $this->stack = array();
        $this->template = preg_replace('~(<\?)~', '<?php echo \'<?\'; ?>', $this->template);
        $this->template = preg_replace('~\{yield\}~', '<?php echo $this->content->process($this->data); ?>', $this->template);
        $this->template = preg_replace('~\{if:(\w+)\}~', '<?php if ($this->data[\'$1\']): ?>', $this->template);
        $this->template = preg_replace('~\{if:(\w+)([!<>=]+)(\w+)\}~', '<?php if ($this->data[\'$1\']$2$this->data[\'$3\']): ?>', $this->template);
        $this->template = preg_replace('~\{ifnot:(\w+)\}~', '<?php if (!$this->data[\'$1\']): ?>', $this->template);
        $this->template = preg_replace('~\{else\}~', '<?php else: ?>', $this->template);
        $this->template = preg_replace('~\{else:(\w+)\}~', '<?php elseif ($this->data[\'$1\']): ?>', $this->template);
        $this->template = preg_replace('~\{else:(\w+)([!<>=]+)(\w+)\}~', '<?php elseif ($this->data[\'$1\']$2$this->data[\'$3\']): ?>', $this->template);
        $this->template = preg_replace('~\{endif\}~', '<?php endif; ?>', $this->template);
        $this->template = preg_replace('~\{loop:(\w+)\}~', '<?php foreach ($this->data[\'$1\'] as $element): $this->wrap($element); ?>', $this->template);
        $this->template = preg_replace('~\{endloop\}~', '<?php $this->unwrap(); endforeach; ?>', $this->template);
        $this->template = preg_replace('~\{(\w+)\}~', '<?php $this->showVariable(\'$1\'); ?>', $this->template);
        $this->template = preg_replace('~\{import:(\w+)\}~', '<?php echo $this->importFile($this->data[\'$1\']); ?>', $this->template);
        $this->template = preg_replace('~\{escape:(\w+)\}~', '<?php $this->showVariable(\'$1\', true); ?>', $this->template);
        $this->template = '?>' . $this->template;
        return $this->run($this->template);
    }
}