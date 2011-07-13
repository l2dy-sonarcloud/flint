$(document).ready(function(){
    $('.remove').click(function(e){
        e.preventDefault();

        var response = confirm('Are you sure you want to delete your account and all your repositories? This action cannot be undone!');

        if (response) {
            window.location = $(this).attr('href');
        }
    });
});
