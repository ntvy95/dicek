/**
*  jQuery Tooltip Plugin
*  @requires jQuery v1.3 or 1.4
*  http://intekhabrizvi.wordpress.com/
*
*  Copyright (c)  Intekhab A Rizvi (intekhabrizvi.wordpress.com)
*  Licensed under GPL licenses:
*  http://www.gnu.org/licenses/gpl.html
* 
*  Version: 3.2.2
*  Dated : 24-Mar-2010
*	
* Modified by hyutars
*	
*/
(function ($) {
    jQuery.fn.tooltip = function (options) {
        var defaults = {
            offsetX: 20,  //X Offset value
            offsetY: 20,  //Y Offset value
            fadeIn: '200', //Tooltip fadeIn speed, can use, slow, fast, number
            fadeOut: '200', //Tooltip fadeOut speed, can use, slow, fast, number
            dataAttr: 'data', //Used when we create seprate div to hold your tooltip data, so plugin search div tage by using id 'data' and current href id on whome the mouse pointer is so if your href id is '_tooltip_1' then the div which hold that tooltips content should have id 'data_tooltip_1', if you change dataAttr from default then you need to build div tag with id 'current dataAttr _tooltip_1' without space
            bordercolor: '#c1e0ea', // tooltip border color
            bgcolor: '#FFFFFF', //Tooltip background color
            folderurl: 'NULL', // Folder url, where the tooltip's content file is placed, needed with forward slash in the last (/), or can be use as http://www.youwebsitename.com/foldername/ also.
            filetype: 'txt', // tooltip's content files type, can be use html, txt
            height: 'auto', // Tooltip's width
            width: 'auto', //Tooltip's Height
            cursor: 'help' // Mouse cursor
        };
        var options = $.extend(defaults, options);
        //Runtime div building to hold tooltip data, and make it hidden
        var $tooltip = $('<div id="divToolTip"></div>');
        return this.each(function () {
            $('body').append($tooltip);
            $tooltip.hide();
            //Runtime variable definations
            var element = this;
            var id = $(element).attr('id');
            var filename = options.folderurl + id + '.' + options.filetype;
            var dialog_id = '#divToolTip';
            //Tooltips main function
            $(this).hover(function (e) {
                //to check whether the tooltips content files folder is defined or not
                if (options.folderurl != "NULL") {
                    $(dialog_id).load(filename);

                } else {
                    if ($('#' + options.dataAttr + '_' + id).length > 0) {
                        $(dialog_id).html($('#' + options.dataAttr + '_' + id).html());
                        //$(dialog_id).html(size);
                    } else {
                        $(dialog_id).html(id);
                        //$(dialog_id).html(size);
                    }
                }
                //assign css value to div
                $(element).css({ 'cursor': options.cursor });
                var left = 0, top = 0;
                if (window.innerWidth / 2 < e.clientX) {
                    left = e.pageX - $(dialog_id).width() - options.offsetX;
                } else {
                    left = e.pageX + options.offsetX;
                }

                if (window.innerHeight / 2 < e.clientY) {
                    top = e.pageY - $(dialog_id).height() - options.offsetY;                    
                }
                else
                    top = e.pageY + options.offsetY;                

                $(dialog_id).css({
                    'position': 'absolute',
                    'border': '1px solid ' + options.bordercolor,
                    'background-color': options.bgcolor,
                    'padding': '5px 5px 5px 5px',
                    '-moz-border-radius': '5px 5px 5px 5px',
                    '-webkit-border-radius': '5px 5px 5px 5px',
                    'top': top,
                    'left': left,
                    'color': options.fontcolor,
                    'font-size': options.fontsize,
                    'height': options.height,
                    'width': options.width
                });

                //enable div block
                $(dialog_id).stop(true, true).fadeIn(options.fadeIn);
            }, function () {
                // when mouse out remove all data from div and make it hidden
                $(dialog_id).stop(true, true).fadeOut(options.fadeOut);
            })
        });
    };
})(jQuery);

//i.rizvi@hotmail.com
