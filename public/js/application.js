$(function() {
	$('.delete').click(function(event) {
		event.preventDefault();
		var answer = confirm("Are You sure?");
		if(answer) {
			var $this = $(this);

			var $form = $('<form method="post" style="display: none;" action="' + $this.attr('href') + '"><input type="hidden" name="_METHOD" value="DELETE"/><button class="submit">Submit</button></form>');
	 		$this.after($form);
	 		$form.submit();
	 	}

	})
});