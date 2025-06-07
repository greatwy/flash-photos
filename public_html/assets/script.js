document.addEventListener('DOMContentLoaded', function() {
    const uploadArea = document.getElementById('upload-area');
    const imageInput = document.getElementById('image-input');
    const uploadLabel = document.querySelector('.upload-label');
    const uploadForm = document.getElementById('upload-form');
    const submitButton = document.getElementById('submit-button');

    // 上传区域点击处理
    if (uploadLabel) {
        uploadLabel.addEventListener('click', function(e) {
            if (!imageInput.files || !imageInput.files.length) {
                imageInput.click();
            }
            e.stopPropagation();
        });
    }

    // 文件选择处理
    if (imageInput) {
        imageInput.addEventListener('change', function() {
            if (this.files && this.files.length) {
                const fileNameDisplay = uploadArea.querySelector('p:first-child');
                fileNameDisplay.textContent = `已选择: ${this.files[0].name}`;
                uploadArea.classList.add('has-file');
            }
        });
    }

    // 拖拽功能
    if (uploadArea) {
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', function() {
            this.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            
            if (e.dataTransfer.files && e.dataTransfer.files.length) {
                imageInput.files = e.dataTransfer.files;
                const fileNameDisplay = uploadArea.querySelector('p:first-child');
                fileNameDisplay.textContent = `已选择: ${e.dataTransfer.files[0].name}`;
                uploadArea.classList.add('has-file');
            }
        });
    }

    // 表单提交处理
    if (uploadForm && submitButton) {
        uploadForm.addEventListener('submit', function() {
            submitButton.disabled = true;
            submitButton.textContent = '上传中...';
        });
    }
});