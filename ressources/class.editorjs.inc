<?php

class editorjs{

    public function __construct(){



    }


    public function field_editorjs($name,$label,$value,$explain=null):string{
        $id=md5("$name$this->suffixid");

        $Header="Header$id";
        $this->JS[]="if (typeof editor$id === 'undefined') {";
        $this->JS[]="const editor$id = new EditorJS({";
        $this->JS[]="\tholder: '$id',";
        $this->JS[]=" tools: {";
        $this->JS[]="        header: {";
        $this->JS[]="          class: Header,";
        $this->JS[]="          inlineToolbar: ['marker', 'link'],";
        $this->JS[]="          config: {";
        $this->JS[]="            placeholder: 'Header'";
        $this->JS[]="          },";
        $this->JS[]="          shortcut: 'CMD+SHIFT+H'";
        $this->JS[]="        },";
        $this->JS[]="        image: SimpleImage,";
        $this->JS[]=$this->layout();
        $this->JS[]="        list: {";
        $this->JS[]="          class: List,";
        $this->JS[]="          inlineToolbar: true,";
        $this->JS[]="          shortcut: 'CMD+SHIFT+L'";
        $this->JS[]="        },";
        $this->JS[]="";
        $this->JS[]="        checklist: {";
        $this->JS[]="          class: Checklist,";
        $this->JS[]="          inlineToolbar: true,";
        $this->JS[]="        },";
        $this->JS[]="";
        $this->JS[]="        quote: {";
        $this->JS[]="          class: Quote,";
        $this->JS[]="          inlineToolbar: true,";
        $this->JS[]="          config: {";
        $this->JS[]="            quotePlaceholder: 'Enter a quote',";
        $this->JS[]="            captionPlaceholder: 'Quote\'s author',";
        $this->JS[]="          },";
        $this->JS[]="          shortcut: 'CMD+SHIFT+O'";
        $this->JS[]="        },";
        $this->JS[]="";
        $this->JS[]="        warning: Warning,";
        $this->JS[]="";
        $this->JS[]="        marker: {";
        $this->JS[]="          class:  Marker,";
        $this->JS[]="          shortcut: 'CMD+SHIFT+M'";
        $this->JS[]="        },";
        $this->JS[]="";
        $this->JS[]="        code: {";
        $this->JS[]="          class:  CodeTool,";
        $this->JS[]="          shortcut: 'CMD+SHIFT+C'";
        $this->JS[]="        },";
        $this->JS[]="";
        $this->JS[]="        delimiter: Delimiter,";
        $this->JS[]="";
        $this->JS[]="        inlineCode: {";
        $this->JS[]="          class: InlineCode,";
        $this->JS[]="          shortcut: 'CMD+SHIFT+C'";
        $this->JS[]="        },";
        $this->JS[]="";
        $this->JS[]="        linkTool: LinkTool,";
        $this->JS[]="";
        $this->JS[]="        embed: Embed,";
        $this->JS[]="";
        $this->JS[]="        table: {";
        $this->JS[]="          class: Table,";
        $this->JS[]="          inlineToolbar: true,";
        $this->JS[]="          shortcut: 'CMD+ALT+T'";
        $this->JS[]="        },";
        $this->JS[]="";
        $this->JS[]="      },";
        $this->JS[]="});";
        $this->JS[]="}";
        if($label==null){$width=";width:100%";}
        return $this->_field_intr($label,
            "<div id='$Header'></div><div id='$id' name='$id' style='min-height:50px;$width;border:1px solid black' rows='5'>$value</div>",$explain);

    }

    function layout(){
        return "layout: {
        class: EditorJSLayout.LayoutBlockTool,
        config: {
            EditorJS,
            editorJSConfig,
            enableLayoutEditing: false,
            enableLayoutSaving: true,
            initialData: {
                itemContent: {
                    1: {
                        blocks: [],
                    },
                },
                layout: {
                    type: \"container\",
                    id: \"\",
                    className: \"\",
                    style: \"border: 1px solid #000000; \",
                    children: [
                        {
                            type: \"item\",
                            id: \"\",
                            className: \"\",
                            style: \"border: 1px solid #000000; display: inline-block; \",
                            itemContentId: \"1\",
                        },
                    ],
                },
            },
        },
    },
    twoColumns: {
        class: EditorJSLayout.LayoutBlockTool,
        config: {
            EditorJS,
            editorJSConfig,
            enableLayoutEditing: false,
            enableLayoutSaving: false,
            initialData: {
                itemContent: {
                    1: {
                        blocks: [],
                    },
                    2: {
                        blocks: [],
                    },
                },
                layout: {
                    type: \"container\",
                    id: \"\",
                    className: \"\",
                    style:
                        \"border: 1px solid #000000; display: flex; justify-content: space-around; padding: 16px; \",
                    children: [
                        {
                            type: \"item\",
                            id: \"\",
                            className: \"\",
                            style: \"border: 1px solid #000000; padding: 8px; \",
                            itemContentId: \"1\",
                        },
                        {
                            type: \"item\",
                            id: \"\",
                            className: \"\",
                            style: \"border: 1px solid #000000; padding: 8px; \",
                            itemContentId: \"2\",
                        },
                    ],
                },
            },
        },
        shortcut: \"CMD+2\",
        toolbox: {
            icon: `
              <svg xmlns='http://www.w3.org/2000/svg' width=\"16\" height=\"16\" viewBox='0 0 512 512'>
                <rect x='128' y='128' width='336' height='336' rx='57' ry='57' fill='none' stroke='currentColor' stroke-linejoin='round' stroke-width='32'/>
                <path d='M383.5 128l.5-24a56.16 56.16 0 00-56-56H112a64.19 64.19 0 00-64 64v216a56.16 56.16 0 0056 56h24' fill='none' stroke='currentColor' stroke-linecap='round' stroke-linejoin='round' stroke-width='32'/>
              </svg>
            `,
            title: \"2 columns\",
        },
    },";
    }

}