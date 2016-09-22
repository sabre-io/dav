var deleteLink = document.querySelectorAll('.deleteButton');

for (var i = 0; i < deleteLink.length; i++) {
    deleteLink[i].addEventListener('click', function(event) {
        if (!confirm("Are you sure you want to delete this item?")) {
            event.preventDefault();
        }
    });
}