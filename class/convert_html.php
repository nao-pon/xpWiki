<?php

// Block elements
class XpWikiElement {
	var $parent;
	var $elements; // References of childs
	var $last; // Insert new one at the back of the $last
	
	var $flg; // Any flag

	var $xpwiki;

	function XpWikiElement(& $xpwiki) {

		$this->xpwiki = & $xpwiki;
		$this->root = & $xpwiki->root;
		$this->cont = & $xpwiki->cont;
		$this->func = & $xpwiki->func;
		
		$this->elements = array ();
		$this->last = & $this;
	}

	function setParent(& $parent) {
		$this->parent = & $parent;
	}

	function & add(& $obj) {
		if ($this->canContain($obj)) {
			return $this->insert($obj);
		} else {
			return $this->parent->add($obj);
		}
	}

	function & insert(& $obj) {
		$obj->setParent($this);
		$this->elements[] = & $obj;

		return $this->last = & $obj->last;
	}

	function canContain($obj) {
		return TRUE;
	}

	function wrap($string, $tag, $param = '', $canomit = TRUE) {
		return ($canomit && $string == '') ? '' : '<'.$tag.$param.'>'.$string.'</'.$tag.'>';
	}

	function toString() {
		$ret = array ();
		foreach (array_keys($this->elements) as $key) {
			$ret[] = $this->elements[$key]->toString();
			$this->elements[$key] = null;
		}
		return join("\n", $ret);
	}

	function dump($indent = 0) {
		$ret = str_repeat(' ', $indent).get_class($this)."\n";
		$indent += 2;
		foreach (array_keys($this->elements) as $key) {
			$ret .= is_object($this->elements[$key]) ? $this->elements[$key]->dump($indent) : '';
			//str_repeat(' ', $indent) . $this->elements[$key];
		}
		return $ret;
	}
}

// Inline elements
class XpWikiInline extends XpWikiElement {
	function XpWikiInline(& $xpwiki, $text) {
		parent :: XpWikiElement($xpwiki);
		$this->elements[] = trim((substr($text, 0, 1) == "\n") ? $text : $this->func->make_link($text));
	}

	function & insert(& $obj) {
		$this->elements[] = $obj->elements[0];
		return $this;
	}

	function canContain($obj) {
		return is_a($obj, 'XpWikiInline');
	}

	function toString() {
		//		global $line_break;
		return join(($this->root->line_break ? '<br />'."\n" : "\n"), $this->elements);
	}

	function & toPara($class = '') {
		$obj = & new XpWikiParagraph($this->xpwiki, '', $class);
		$obj->insert($this);
		return $obj;
	}
}

// Paragraph: blank-line-separated sentences
class XpWikiParagraph extends XpWikiElement {
	var $param;

	function XpWikiParagraph(& $xpwiki, $text, $param = '') {
		parent :: XpWikiElement($xpwiki);
		$this->param = $param;
		if ($text == '')
			return;

		if (substr($text, 0, 1) == '~')
			$text = ' '.substr($text, 1);
		$this->insert($this->func->Factory_Inline($text));
	}

	function canContain($obj) {
		return is_a($obj, 'XpWikiInline');
	}

	function toString() {
		return $this->wrap(parent :: toString(), 'p', $this->param);
	}
}

// * Heading1
// ** Heading2
// *** Heading3
class XpWikiHeading extends XpWikiElement {
	var $level;
	var $id;
	var $msg_top;

	function XpWikiHeading(& $root, $text) {
		parent :: XpWikiElement($root->xpwiki);

		$this->level = min(5, strspn($text, '*'));
		list ($text, $this->msg_top, $this->id) = $root->getAnchor($text, $this->level);
		$this->insert($root->func->Factory_Inline($text));
		$this->level++; // h2,h3,h4
	}

	function & insert(& $obj) {
		parent :: insert($obj);
		return $this->last = & $this;
	}

	function canContain(& $obj) {
		return FALSE;
	}

	function toString() {
		return $this->msg_top.$this->wrap(parent :: toString(), 'h'.$this->level, ' id="'.$this->id.'"');
	}
}

// ----
// Horizontal Rule
class XpWikiHRule extends XpWikiElement {
	function XpWikiHRule(& $root, $text) {
		parent :: XpWikiElement($root->xpwiki);
	}

	function canContain(& $obj) {
		return FALSE;
	}

	function toString() {
		//		global $hr;
		return $this->root->hr;
	}
}

// Lists (UL, OL, DL)
class XpWikiListContainer extends XpWikiElement {
	var $tag;
	var $tag2;
	var $level;
	var $style;
	var $margin;
	var $left_margin;

	function XpWikiListContainer(& $xpwiki, $tag, $tag2, $head, $text) {
		parent :: XpWikiElement($xpwiki);

		$var_margin = '_'.$tag.'_margin';
		$var_left_margin = '_'.$tag.'_left_margin';
		//		global $$var_margin, $$var_left_margin;

		$this->margin = $this->root-> $var_margin;
		$this->left_margin = $this->root-> $var_left_margin;

		$this->tag = $tag;
		$this->tag2 = $tag2;
		$this->level = strspn($text, $head);
		$text = ltrim(substr($text, $this->level));

		parent :: insert(new XpWikiListElement($this->xpwiki, $this->level, $tag2));
		//print_r($this->func->Factory_Inline);exit;
		if ($text != '')
			$this->last = & $this->last->insert($this->func->Factory_Inline($text));
	}

	function canContain(& $obj) {
		return (!is_a($obj, 'XpWikiListContainer') || ($this->tag == $obj->tag && $this->level == $obj->level));
	}

	function setParent(& $parent) {
		//		global $_list_pad_str;

		parent :: setParent($parent);

		$step = $this->level;
		if (isset ($parent->parent) && is_a($parent->parent, 'XpWikiListContainer'))
			$step -= $parent->parent->level;

		$margin = $this->margin * $step;
		if ($step == $this->level)
			$margin += $this->left_margin;

		$this->style = sprintf($this->root->_list_pad_str, $this->level, $margin, $margin);
	}

	function & insert(& $obj) {
		if (!is_a($obj, get_class($this)))
			return $this->last = & $this->last->insert($obj);

		// Break if no elements found (BugTrack/524)
		if (count($obj->elements) == 1 && empty ($obj->elements[0]->elements))
			return $this->last->parent; // up to ListElement

		// Move elements
		foreach (array_keys($obj->elements) as $key)
			parent :: insert($obj->elements[$key]);

		return $this->last;
	}

	function toString() {
		return $this->wrap(parent :: toString(), $this->tag, $this->style);
	}
}

class XpWikiListElement extends XpWikiElement {
	function XpWikiListElement(& $xpwiki, $level, $head) {
		parent :: XpWikiElement($xpwiki);
		$this->level = $level;
		$this->head = $head;
	}

	function canContain(& $obj) {
		return (!is_a($obj, 'XpWikiListContainer') || ($obj->level > $this->level));
	}

	function toString() {
		return $this->wrap(parent :: toString(), $this->head);
	}
}

// - One
// - Two
// - Three
class XpWikiUList extends XpWikiListContainer {
	function XpWikiUList(& $root, $text) {
		parent :: XpWikiListContainer($root->xpwiki, 'ul', 'li', '-', $text);
	}
}

// + One
// + Two
// + Three
class XpWikiOList extends XpWikiListContainer {
	function XpWikiOList(& $root, $text) {
		parent :: XpWikiListContainer($root->xpwiki, 'ol', 'li', '+', $text);
	}
}

// : definition1 | description1
// : definition2 | description2
// : definition3 | description3
class XpWikiDList extends XpWikiListContainer {
	function XpWikiDList(& $xpwiki, $out) {
		parent :: XpWikiListContainer($xpwiki, 'dl', 'dt', ':', $out[0]);
		$this->last = & XpWikiElement :: insert(new XpWikiListElement($xpwiki, $this->level, 'dd'));
		if ($out[1] != '')
			$this->last = & $this->last->insert($xpwiki->func->Factory_Inline($out[1]));
	}
}

// > Someting cited
// > like E-mail text
class XpWikiBQuote extends XpWikiElement {
	var $level;

	function XpWikiBQuote(& $root, $text) {
		parent :: XpWikiElement($root->xpwiki);

		$head = substr($text, 0, 1);
		$this->level = min(3, strspn($text, $head));
		$text = ltrim(substr($text, $this->level));

		if ($head == '<') { // Blockquote close
			$level = $this->level;
			$this->level = 0;
			$this->last = & $this->end($root, $level);
			if ($text != '')
				$this->last = & $this->last->insert($this->func->Factory_Inline($text));
		} else {
			$this->insert($this->func->Factory_Inline($text));
		}
	}

	function canContain(& $obj) {
		return (!is_a($obj, get_class($this)) || $obj->level >= $this->level);
	}

	function & insert(& $obj) {
		// BugTrack/521, BugTrack/545
		if (is_a($obj, 'XpWikiinline'))
			return parent :: insert($obj->toPara(' class="quotation"'));

		if (is_a($obj, 'XpWikiBQuote') && $obj->level == $this->level && count($obj->elements)) {
			$obj = & $obj->elements[0];
			if (is_a($this->last, 'XpWikiParagraph') && count($obj->elements))
				$obj = & $obj->elements[0];
		}
		return parent :: insert($obj);
	}

	function toString() {
		return $this->wrap(parent :: toString(), 'blockquote');
	}

	function & end(& $root, $level) {
		$parent = & $root->last;

		while (is_object($parent)) {
			if (is_a($parent, 'XpWikiBQuote') && $parent->level == $level)
				return $parent->parent;
			$parent = & $parent->parent;
		}
		return $this;
	}
}

class XpWikiTableCell extends XpWikiElement {
	var $tag = 'td'; // {td|th}
	var $colspan = 1;
	var $rowspan = 1;
	var $style; // is array('width'=>, 'align'=>...);

	function XpWikiTableCell(& $xpwiki, $text, $is_template = FALSE) {
		parent :: XpWikiElement($xpwiki);
		$this->style = $matches = array ();
		
		if ($this->root->extended_table_format) {
			$text = $this->get_cell_style($text);
		}
		
		while (preg_match('/^(?:(LEFT|CENTER|RIGHT)|(BG)?COLOR\(([#\w]+)\)|SIZE\((\d+)\)):(.*)$/', $text, $matches)) {
			if ($matches[1]) {
				$this->style['align'] = 'text-align:'.strtolower($matches[1]).';';
				$text = $matches[5];
			} else
				if ($matches[3]) {
					$name = $matches[2] ? 'background-color' : 'color';
					$this->style[$name] = $name.':'.htmlspecialchars($matches[3]).';';
					$text = $matches[5];
				} else
					if ($matches[4]) {
						$this->style['size'] = 'font-size:'.htmlspecialchars($matches[4]).'px;';
						$text = $matches[5];
					}
		}
		if ($is_template && is_numeric($text))
			$this->style['width'] = 'width:'.$text.'px;';

		if ($text == '>') {
			$this->colspan = 0;
		} else
			if ($text == '~') {
				$this->rowspan = 0;
			} else
				if (substr($text, 0, 1) == '~') {
					$this->tag = 'th';
					$text = substr($text, 1);
				}

		if ($text != '' && $text { 0 } == '#') {
			// Try using Div class for this $text
			$obj = & $this->func->Factory_Div($text);
			if (is_a($obj, 'XpWikiParagraph'))
				$obj = & $obj->elements[0];
		} else {
			$obj = & $this->func->Factory_Inline($text);
		}

		$this->insert($obj);
	}

	function setStyle(& $style) {
		foreach ($style as $key => $value)
			if (!isset ($this->style[$key]))
				$this->style[$key] = $value;
	}

	function toString() {
		if ($this->rowspan == 0 || $this->colspan == 0)
			return '';

		$param = ' class="style_'.$this->tag.'"';
		if ($this->rowspan > 1)
			$param .= ' rowspan="'.$this->rowspan.'"';
		if ($this->colspan > 1) {
			$param .= ' colspan="'.$this->colspan.'"';
			unset ($this->style['width']);
		}
		if (!empty ($this->style))
			$param .= ' style="'.join(' ', $this->style).'"';

		return $this->wrap(parent :: toString(), $this->tag, $param, FALSE);
	}

	function get_cell_style($string) {
		$cells = explode('|',$string,2);
//		echo "CELL: {$cells[0]}\n";
		$colors_reg = "aqua|navy|black|olive|blue|purple|fuchsia|red|gray|silver|green|teal|lime|white|maroon|yellow|transparent";
		$this->style['color'] = '';
		if (preg_match("/FC:(#?[0-9abcdef]{6}?|$colors_reg|0)/i",$cells[0],$tmp)) {
			if ($tmp[1]==="0") $tmp[1]="transparent";
			$this->style['fcolor'] = "color:".$tmp[1].";";
			$cells[0] = preg_replace("/FC:(#?[0-9abcdef]{6}?|$colors_reg|0)(\(([^),]*)(,no|,one|,1)?\))/i","FC:$2",$cells[0]);
			$cells[0] = preg_replace("/FC:(#?[0-9abcdef]{6}?|$colors_reg|0)/i","",$cells[0]);
		}
		// セル規定背景色指定
		if (preg_match("/(?:SC|BC):(#?[0-9abcdef]{6}?|$colors_reg|0)/i",$cells[0],$tmp)) {
			if ($tmp[1]==="0") $tmp[1]="transparent";
			$this->style['color'] = "background-color:".$tmp[1].";";
			$cells[0] = preg_replace("/(?:SC|BC):(#?[0-9abcdef]{6}?|$colors_reg|0)(\(([^),]*)(,no|,one|,1)?\))/i","BC:$2",$cells[0]);
			$cells[0] = preg_replace("/(?:SC|BC):(#?[0-9abcdef]{6}?|$colors_reg|0)/i","",$cells[0]);
		}
		// セル規定背景画指定
		if (preg_match("/(?:SC|BC):\(([^),]*)(,once|,1)?\)/i",$cells[0],$tmp)) {
			$tmp[1] = str_replace("http","HTTP",$tmp[1]);
			$this->style['color'] .= "background-image: url(".$tmp[1].");";
			if (!empty($tmp[2])) $this->style['color'] .= "background-repeat: no-repeat;";
			$cells[0] = preg_replace("/(?:SC|BC):\(([^),]*)(,once|,1)?\)/i","",$cells[0]);
		}
		if (preg_match("/K:([0-9]+),?([0-9]*)(one|two|boko|deko|in|out|dash|dott)?/i",$cells[0],$tmp)) {
			if (array_key_exists (3,$tmp)) {
				switch (strtolower($tmp[3])) {
					case 'one':
				 		$border_type = "solid";
				 		break;
					case 'two':
						$border_type = "double";
				 		break;
					case 'boko':
						$border_type = "groove";
				 		break;
					case 'deko':
						$border_type = "ridge";
				 		break;
					case 'in':
						$border_type = "inset";
				 		break;
					case 'out':
						$border_type = "outset";
				 		break;
					case 'dash':
						$border_type = "dashed";
				 		break;
					case 'dott':
						$border_type = "dotted";
				 		break;
					default:
						$border_type = "outset";
				}
			} else {
				$border_type = "outset";
			}
			//$this->table_style .= " border=\"".$tmp[1]."\"";
			if (array_key_exists (1,$tmp)) {
				if ($tmp[1]==="0"){
					$this->style['border'] = "border:none;";
				} else {
					$this->style['border'] = "border:".$border_type." ".$tmp[1]."px;";
				}
			}
			if (array_key_exists (2,$tmp)) {
				if ($tmp[2]!=""){
					$this->style['padding'] = " padding:".$tmp[2].";";
				} else {
					$this->style['padding'] = " padding:5px;";
				}
			}
			$cells[0] = preg_replace("/K:([0-9]+),?([0-9]*)(one|two|boko|deko|in|out|dash|dott)?/i","",$cells[0]);
		} else {
//			$this->style['border'] = "border:none;";
		}
		// ボーダー色指定
		if (preg_match("/KC:(#?[0-9abcdef]{6}?|$colors_reg|0)/i",$cells[0],$tmp)) {
			if ($tmp[1]==="0") $tmp[1]="transparent";
			$this->style['border-color'] = "border-color:".$tmp[1].";";
			$cells[0] = preg_replace("/KC:(#?[0-9abcdef]{6}?|$colors_reg|0)/i","",$cells[0]);
		}
		// セル規定文字揃え、幅指定
		if (preg_match("/(?:^ *)(?:(LEFT|CENTER|RIGHT)?:(TOP|MIDDLE|BOTTOM)?)?(?::([0-9]+[%]?))?/i",$cells[0],$tmp)) {
			//var_dump($tmp); echo "<br>\n";
			if (@$tmp[1] || @$tmp[2] || @$tmp[3]) {
				if (@$tmp[3]) {
					if (!strpos($tmp[3],"%")) $tmp[3] .= "px";
					$this->style['width'] = "width:".$tmp[3].";";
				}
				if (@$tmp[1]) $this->style['align'] = "text-align:".strtolower($tmp[1]).";";
				if (@$tmp[2]) $this->style['valign'] = "vertical-align:".strtolower($tmp[2]).";";
				$cells[0] = preg_replace("/(?:^ *)(?:(LEFT|CENTER|RIGHT)?:(TOP|MIDDLE|BOTTOM)?)?(?::([0-9]+[%]?))?/i","",$cells[0]);
			}
		}
		return implode('|',$cells);
	}
}

// | title1 | title2 | title3 |
// | cell1  | cell2  | cell3  |
// | cell4  | cell5  | cell6  |
class XpWikiTable extends XpWikiElement {
	var $type;
	var $types;
	var $col; // number of column
	var $table_around,$table_sheet,$table_style,$div_style,$table_align;

	function XpWikiTable(& $xpwiki, $out) {
		parent :: XpWikiElement($xpwiki);

		$cells = explode('|', $out[1]);

		$this->col = count($cells);
		$this->type = strtolower($out[2]);
		$this->types = array ($this->type);
		$is_template = ($this->type == 'c');
		
		if ($this->root->extended_table_format && $is_template) {
			$cells[0] = $this->get_table_style($cells[0]);
		}
		
		$row = array ();
		foreach ($cells as $cell)
			$row[] = & new XpWikiTableCell($this->xpwiki, $cell, $is_template);
		$this->elements[] = $row;
	}

	function canContain(& $obj) {
		return is_a($obj, 'XpWikiTable') && ($obj->col == $this->col);
	}

	function & insert(& $obj) {
		$this->elements[] = $obj->elements[0];
		$this->types[] = $obj->type;
		return $this;
	}

	function toString() {
		static $parts = array ('h' => 'thead', 'f' => 'tfoot', '' => 'tbody');

		// Set rowspan (from bottom, to top)
		for ($ncol = 0; $ncol < $this->col; $ncol ++) {
			$rowspan = 1;
			foreach (array_reverse(array_keys($this->elements)) as $nrow) {
				$row = & $this->elements[$nrow];
				if ($row[$ncol]->rowspan == 0) {
					++ $rowspan;
					continue;
				}
				$row[$ncol]->rowspan = $rowspan;
				// Inherits row type
				while (-- $rowspan)
					$this->types[$nrow + $rowspan] = $this->types[$nrow];
				$rowspan = 1;
			}
		}

		// Set colspan and style
		$stylerow = NULL;
		foreach (array_keys($this->elements) as $nrow) {
			$row = & $this->elements[$nrow];
			if ($this->types[$nrow] == 'c')
				$stylerow = & $row;
			$colspan = 1;
			foreach (array_keys($row) as $ncol) {
				if ($row[$ncol]->colspan == 0) {
					++ $colspan;
					continue;
				}
				$row[$ncol]->colspan = $colspan;
				if ($stylerow !== NULL) {
					$row[$ncol]->setStyle($stylerow[$ncol]->style);
					// Inherits column style
					while (-- $colspan)
						$row[$ncol - $colspan]->setStyle($stylerow[$ncol]->style);
				}
				$colspan = 1;
			}
		}

		// toString
		$string = '';
		foreach ($parts as $type => $part) {
			$part_string = '';
			foreach (array_keys($this->elements) as $nrow) {
				if ($this->types[$nrow] != $type)
					continue;
				$row = & $this->elements[$nrow];
				$row_string = '';
				foreach (array_keys($row) as $ncol)
					$row_string .= $row[$ncol]->toString();
				$part_string .= $this->wrap($row_string, 'tr');
			}
			$string .= $this->wrap($part_string, $part);
		}
		//$string = $this->wrap($string, 'table', ' class="style_table" cellspacing="1" border="0"');

		//return $this->wrap($string, 'div', ' class="ie5"');
		
		$string = $this->wrap($string, 'table', ' class="style_table"'."$this->table_style style=\"$this->table_sheet\"");
		
		return $this->wrap($string, 'div', ' class="ie5" '.$this->div_style).$this->table_around;

	}

	function get_table_style($string) {
//		echo "TABLE: $string <br>\n";
		$colors_reg = "aqua|navy|black|olive|blue|purple|fuchsia|red|gray|silver|green|teal|lime|white|maroon|yellow|transparent";
		//$this->table_around = "<br clear=all /><br />";
		$this->table_around = "<br clear=all />";
		// 回り込み指定
		if (preg_match("/AROUND/i",$string)) $this->table_around = "";
		// ボーダー指定
		if (preg_match("/B:([0-9]+),?([0-9]*)(one|two|boko|deko|in|out|dash|dott)?/i",$string,$reg)) {
			if (array_key_exists (3,$reg)) {
				switch (strtolower($reg[3])) {
					case 'one':
				 		$border_type = "solid";
				 		break;
					case 'two':
						$border_type = "double";
				 		break;
					case 'boko':
						$border_type = "groove";
				 		break;
					case 'deko':
						$border_type = "ridge";
				 		break;
					case 'in':
						$border_type = "inset";
				 		break;
					case 'out':
						$border_type = "outset";
				 		break;
					case 'dash':
						$border_type = "dashed";
				 		break;
					case 'dott':
						$border_type = "dotted";
				 		break;
					default:
						$border_type = "outset";
				}
			} else {
				$border_type = "outset";
			}
			
			//$this->table_style .= " border=\"".$reg[1]."\"";
			if (array_key_exists (1,$reg)) {
				if ($reg[1]==="0"){
					$this->table_sheet .= "border:none;";
				} else {
					$this->table_sheet .= "border:".$border_type." ".$reg[1]."px;";
				}
			}
			if (array_key_exists (2,$reg)) {
				if ($reg[2]!=""){
					$this->table_style .= " cellspacing=\"".$reg[2]."\"";
				} else {
					$this->table_style .= " cellspacing=\"1\"";
				}
			}
			$string = preg_replace("/B:([0-9]+),?([0-9]*)(one|two|boko|deko|in|out|dash|dott)?/i","",$string);
		} else {
			$this->table_style .= " border=\"0\" cellspacing=\"1\"";
			//$this->table_style .= " cellspacing=\"1\"";
			//$this->table_sheet .= "border:none;";
		}
		// ボーダー色指定
		if (preg_match("/BC:(#?[0-9abcdef]{6}?|$colors_reg|0)/i",$string,$reg)) {
			$this->table_sheet .= "border-color:".$reg[1].";";
			$string = preg_replace("/BC:(#?[0-9abcdef]{6}?|$colors_reg)/i","",$string);
		}
		// テーブル背景色指定
		if (preg_match("/TC:(#?[0-9abcdef]{6}?|$colors_reg|0)/i",$string,$reg)) {
			if ($reg[1]==="0") $reg[1]="transparent";
			$this->table_sheet .= "background-color:".$reg[1].";";
			$string = preg_replace("/TC:(#?[0-9abcdef]{6}?|$colors_reg|0)(\(([^),]*)(,no|,one|,1)?\))/i","TC:$2",$string);
			$string = preg_replace("/TC:(#?[0-9abcdef]{6}?|$colors_reg|0)/i","",$string);
		}
		// テーブル背景画像指定
		if (preg_match("/TC:\(([^),]*)(,once|,1)?\)/i",$string,$reg)) {
			$reg[1] = str_replace("http","HTTP",$reg[1]);
			$this->table_sheet .= "background-image: url(".$reg[1].");";
			if (!empty($reg[2])) $this->table_sheet .= "background-repeat: no-repeat;";
			$string = preg_replace("/TC:\(([^),]*)(,once|,1)?\)/i","",$string);
		}
		// 配置・幅指定
		if (preg_match("/T(LEFT|RIGHT)/i",$string,$reg)) {
			$this->table_align = strtolower($reg[1]);
			$this->table_style .= " align=\"".$this->table_align."\"";
			$this->div_style = " style=\"text-align:".$this->table_align."\"";
			if ($this->table_align == "left"){
				$this->table_sheet .= "margin-left:10px;margin-right:auto;";
			} else {
				$this->table_sheet .= "margin-left:auto;margin-right:10px;";
			}
		}
		if (preg_match("/T(CENTER)/i",$string,$reg)) {
			$this->table_style .= " align=\"".strtolower($reg[1])."\"";
			$this->div_style = " style=\"text-align:".strtolower($reg[1])."\"";
			$this->table_sheet .= "margin-left:auto;margin-right:auto;";
			$this->table_around = "";
		}
		if (preg_match("/T(LEFT|CENTER|RIGHT)?:([0-9]+(%|px)?)/i",$string,$reg)) {
			$this->table_sheet .= "width:".$reg[2].";";
		}
		$string = preg_replace("/^(TLEFT|TCENTER|TRIGHT|T):([0-9]+(%|px)?)?/i","",$string);
		return ltrim($string);
	}
}

// , cell1  , cell2  ,  cell3 
// , cell4  , cell5  ,  cell6 
// , cell7  ,        right,==
// ,left          ,==,  cell8
class XpWikiYTable extends XpWikiElement {
	var $col;	// Number of columns

	// TODO: Seems unable to show literal '==' without tricks.
	//       But it will be imcompatible.
	// TODO: Why toString() or toXHTML() here
	function XpWikiYTable(& $xpwiki, $row = array('cell1 ', ' cell2 ', ' cell3'))
	{
		parent::XpWikiElement($xpwiki);

		$str = array();
		$col = count($row);

		$matches = $_value = $_align = array();
		foreach($row as $cell) {
			if (preg_match('/^(\s+)?(.+?)(\s+)?$/', $cell, $matches)) {
				if ($matches[2] == '==') {
					// Colspan
					$_value[] = FALSE;
					$_align[] = FALSE;
				} else {
					$_value[] = $matches[2];
					if ($matches[1] == '') {
						$_align[] = '';	// left
					} else if (isset($matches[3])) {
						$_align[] = 'center';
					} else {
						$_align[] = 'right';
					}
				}
			} else {
				$_value[] = $cell;
				$_align[] = '';
			}
		}

		for ($i = 0; $i < $col; $i++) {
			if ($_value[$i] === FALSE) continue;
			$colspan = 1;
			while (isset($_value[$i + $colspan]) && $_value[$i + $colspan] === FALSE) ++$colspan;
			$colspan = ($colspan > 1) ? ' colspan="' . $colspan . '"' : '';
			$align = $_align[$i] ? ' style="text-align:' . $_align[$i] . '"' : '';
			$str[] = '<td class="style_td"' . $align . $colspan . '>';
			$str[] = $this->func->make_link($_value[$i]);
			$str[] = '</td>';
			unset($_value[$i], $_align[$i]);
		}

		$this->col        = $col;
		$this->elements[] = implode('', $str);
	}

	function canContain(& $obj) {
		return is_a($obj, 'XpWikiYTable') && ($obj->col == $this->col);
	}

	function & insert(& $obj) {
		$this->elements[] = $obj->elements[0];
		return $this;
	}

	function toString() {
		$rows = '';
		foreach ($this->elements as $str) {
			$rows .= "\n".'<tr class="style_tr">'.$str.'</tr>'."\n";
		}
		$rows = $this->wrap($rows, 'table', ' class="style_table" cellspacing="1" border="0"');
		return $this->wrap($rows, 'div', ' class="ie5"');
	}
}

// ' 'Space-beginning sentence
// ' 'Space-beginning sentence
// ' 'Space-beginning sentence
class XpWikiPre extends XpWikiElement {
	function XpWikiPre(& $root, $text) {
		//		global $preformat_ltrim;
		parent :: XpWikiElement($root->xpwiki);
		$this->elements[] = htmlspecialchars((!$this->root->preformat_ltrim || $text == '' || $text {
			0}
		!= ' ') ? $text : substr($text, 1));
	}

	function canContain(& $obj) {
		return is_a($obj, 'XpWikiPre');
	}

	function & insert(& $obj) {
		$this->elements[] = $obj->elements[0];
		return $this;
	}

	function toString() {
		return $this->wrap($this->wrap(join("\n", $this->elements), 'pre'), 'div', ' class="pre"');
	}
}

// Block plugin: #something (started with '#')
class XpWikiDiv extends XpWikiElement {
	var $name;
	var $param;

	function XpWikiDiv(& $xpwiki, $out) {
		parent :: XpWikiElement($xpwiki);
		list (, $this->name, $this->param) = array_pad($out, 3, '');
	}

	function canContain(& $obj) {
		return FALSE;
	}

	function toString() {
		// Call #plugin
		return $this->func->do_plugin_convert($this->name, $this->param);
	}
}

// LEFT:/CENTER:/RIGHT:
class XpWikiAlign extends XpWikiElement {
	var $align;

	function XpWikiAlign(& $xpwiki, $align) {
		parent :: XpWikiElement($xpwiki);
		$this->align = $align;
	}

	function canContain(& $obj) {
		return is_a($obj, 'XpWikiInline');
	}

	function toString() {
		return $this->wrap(parent :: toString(), 'div', ' style="text-align:'.$this->align.'"');
	}
}

// Body
class XpWikiBody extends XpWikiElement {
	var $id;
	var $count = 0;
	var $contents;
	var $contents_last;
	var $classes = array (
		'-' => 'XpWikiUList',
		'+' => 'XpWikiOList',
		'>' => 'XpWikiBQuote',
		'<' => 'XpWikiBQuote');
	var $factories = array (
		':' => 'DList',
		'|' => 'Table',
		',' => 'YTable',
		'#' => 'Div');

	function XpWikiBody(& $xpwiki, $id) {
		$this->id = $id;
		$this->contents = & new XpWikiElement($xpwiki);
		$this->contents_last = & $this->contents;
		parent :: XpWikiElement($xpwiki);
	}

	function parse(& $lines) {
		$this->last = & $this;
		$matches = array ();

		while (!empty ($lines)) {
			$line = array_shift($lines);

			// Escape comments
			if (! $this->root->no_slashes_commentout && substr($line, 0, 2) === '//')
				continue;

			if (preg_match('/^(LEFT|CENTER|RIGHT):(.*)$/', $line, $matches)) {
				// <div style="text-align:...">
				$this->last = & $this->last->add(new XpWikiAlign($this->xpwiki, strtolower($matches[1])));
				if ($matches[2] == '')
					continue;
				$line = $matches[2];
			}

			$line = rtrim($line, "\r\n");

			// Empty
			if ($line === '') {
				$this->last = & $this;
				continue;
			}

			// Horizontal Rule
			//if (substr($line, 0, 4) === '----') {
			if (preg_match('/^\-+$/', $line)) {
			
				$this->insert(new XpWikiHRule($this, $line));
				continue;
			}

			// Multiline-enabled block plugin
			if (!$this->cont['PKWKEXP_DISABLE_MULTILINE_PLUGIN_HACK'] && preg_match('/^#[^{]+(\{\{+)\s*$/', $line, $matches)) {
				$len = strlen($matches[1]);
				$line .= "\r"; // Delimiter
				while (!empty ($lines)) {
					$next_line = preg_replace("/[\r\n]*$/", '', array_shift($lines));
					if (preg_match('/\}{'.$len.'}/', $next_line)) {
						$line .= $next_line;
						break;
					} else {
						$line .= $next_line .= "\r"; // Delimiter
					}
				}
			}

			// The first character
			$head = $line {0};
			
			// <, <<, <<< only to escape blockquote.
			if ($head === '<' and !preg_match('/^<{1,3}\s*$/', $line)) {
				$head = '';
			}

			// Heading
			if ($head === '*') {
				$this->insert(new XpWikiHeading($this, $line));
				continue;
			}

			// Pre
			if ($head === ' ' || $head === "\t") {
				$this->last = & $this->last->add(new XpWikiPre($this, $line));
				continue;
			}

			// Line Break
			if (substr($line, -1) === '~')
				$line = substr($line, 0, -1)."\r";

			// Other Character
			if (isset ($this->classes[$head])) {
				$classname = $this->classes[$head];
				$this->last = & $this->last->add(new $classname ($this, $line));
				continue;
			}

			// Other Character
			if (isset ($this->factories[$head])) {
				$factoryname = 'Factory_'.$this->factories[$head];
				$this->last = & $this->last->add($this->func->$factoryname($line));
				continue;
			}

			// Default
			$this->last = & $this->last->add($this->func->Factory_Inline($line));
		}
	}

	function getAnchor($text, $level) {
		//		global $top, $_symbol_anchor;

		// Heading id (auto-generated)
		$autoid = 'content_'.$this->id.'_'.$this->count;
		$this->count++;

		// Heading id (specified by users)
		$id = $this->func->make_heading($text, FALSE); // Cut fixed-anchor from $text
		if ($id == '') {
			// Not specified
			$id = & $autoid;
			$anchor = '';
		} else {
			$anchor = ' &aname(' . $id . ',noid,super,full){'. $this->root->_symbol_anchor . '};';
			if ($this->root->fixed_heading_anchor_edit) $anchor .= " &edit(#$id,paraedit);";
		}

		$text = ' '.$text;

		// Add 'page contents' link to its heading
		$this->contents_last = & $this->contents_last->add(new XpWikiContents_UList($this->xpwiki, $text, $level, $id));
		
		// Area div id
		$area_div = '';
		if (!empty($this->flg['div_area_open'])) {
			$area_div .= "<!--{$this->flg['div_area_open']}--></div>\n";
		}
		$area_div .= '<div id="'.$id.'" class="level'.$level.'">' . "\n";
		$this->flg['div_area_open'] = $id;
		
		// Add heding
		return array ($text.$anchor, $area_div . ($this->count > 1 ? "\n".$this->root->top : ''), $autoid);
	}

	function & insert(& $obj) {
		if (is_a($obj, 'XpWikiInline'))
			$obj = & $obj->toPara();
		return parent :: insert($obj);
	}

	function toString() {
		//		global $vars;

		$text = parent :: toString();
		
		// Close area div
		if (!empty($this->flg['div_area_open'])) {
			$text .= "<!--{$this->flg['div_area_open']}--></div>\n";
		}
		$this->flg['div_area_open'] = false;
		
		// #contents
		$text = preg_replace_callback('/<#_contents_>/', array (& $this, 'replace_contents'), $text);

		return $text."\n";
	}

	function replace_contents($arr) {
		$contents = '<div class="contents">'."\n".'<a id="contents_'.$this->id.'"></a>'."\n".$this->contents->toString()."\n".'</div>'."\n";
		return $contents;
	}
}

class XpWikiContents_UList extends XpWikiListContainer {
	function XpWikiContents_UList(& $xpwiki, $text, $level, $id) {
		parent :: XpWikiListContainer($xpwiki, 'ul', 'li', '-', str_repeat('-', $level));
		// Reformatting $text
		// A line started with "\n" means "preformatted" ... X(
		$this->func->make_heading($text);
		$text = "\n".'<a href="#'.$id.'">'.$text.'</a>'."\n";
		//parent::XpWikiListContainer('ul', 'li', '-', str_repeat('-', $level));
		$this->insert($this->func->Factory_Inline($text));
	}

	function setParent(& $parent) {
		//		global $_list_pad_str;

		parent :: setParent($parent);
		$step = $this->level;
		$margin = $this->left_margin;
		if (isset ($parent->parent) && is_a($parent->parent, 'XpWikiListContainer')) {
			$step -= $parent->parent->level;
			$margin = 0;
		}
		$margin += $this->margin * ($step == $this->level ? 1 : $step);
		$this->style = sprintf($this->root->_list_pad_str, $this->level, $margin, $margin);
	}
}
?>