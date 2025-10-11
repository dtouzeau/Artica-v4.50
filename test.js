$(selector).jstree({
    core: {
        data: {
            url: 'fw.browse-directory.backend.php',
            data: function (node) {
                return { id: node.id };
            }
        },
        check_callback: true,
    },

});