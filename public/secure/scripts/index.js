$(document).ready(function(){
    $('.remove').click(function(e){
        e.preventDefault();

        var response = confirm('Are you sure you want to remove this repository?');

        if (response) {
            window.location = $(this).attr('href');
        }
    });
});
