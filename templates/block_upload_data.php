<form action="<?php echo BASE_URL;?>/upload" method="post" enctype="multipart/form-data">
    <div class="left-half">
        <div id="drop-area" ondrop="dropHandler(event);" ondragover="dragOverHandler(event);">
            <p>Перетащите файл сюда или кликните для выбора файла</p>
            <input type="file" id="file-input" name="file" style="display: none;" />
            <label for="file-input" id="file-label">Browse File</label>
            <span id="file-name"></span>
        </div>
        <input type="submit" name="submit" value="Upload" />
    </div>
</form>

<script>
    function dragOverHandler(event) {
        event.preventDefault();
        event.stopPropagation();
    }

    function dropHandler(event) {
        event.preventDefault();
        event.stopPropagation();

        const files = event.dataTransfer.files;
        document.getElementById('file-input').files = files;
        
        document.getElementById('file-name').innerText = files[0].name;
    }
</script>