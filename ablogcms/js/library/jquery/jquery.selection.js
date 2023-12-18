jQuery.fn.selection = function(txt)
{
    this.focus();
    if ( 'undefined' == typeof(txt) ) {
        if ( document.selection ) {
            return document.selection.createRange().text;
        } else if (this[0].selectionStart != 'undefined') {
            return this.val().substring(this[0].selectionStart, this[0].selectionEnd);
        }
        return false;
    } else {
        if (document.selection) {
            var s	= document.selection.createRange().text;
            if ( 0 <= this.val().indexOf(s) ) {
                document.selection.createRange().text = txt;
            }
        } else if (this[0].selectionStart != 'undefined') {
            this.val(''
                + this.val().substring(0, this[0].selectionStart)
                + txt
                + this.val().substring(this[0].selectionEnd)
            );
        }
    }
    return true;
};
