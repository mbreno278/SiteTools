<div class="workspace__header">
                                <div>
                                    <h2>Una seus arquivos PDF</h2>
                                    <p>
                                        Selecione dois ou mais PDFs.
                                        A ordem selecionada será usada na união.
                                    </p>
                                </div>
                            </div>

                            <form
                                class="tool-form"
                                id="pdf-merge-form"
                                enctype="multipart/form-data"
                            >

                                <label class="upload-zone" for="pdf-files">

                                    <span
                                        class="material-symbols-outlined"
                                        aria-hidden="true"
                                    >picture_as_pdf</span>

                                    <strong>Selecionar arquivos PDF</strong>

                                    <span>Selecione pelo menos dois arquivos</span>

                                    <input
                                        id="pdf-files"
                                        name="files[]"
                                        type="file"
                                        accept="application/pdf,.pdf"
                                        multiple
                                        required
                                    >

                                </label>

                                <ol
                                    class="selected-files"
                                    id="pdf-file-list"
                                ></ol>

                                <p class="form-help">
                                    Os arquivos devem ser validados pelo backend
                                    antes do processamento.
                                </p>

                                <button
                                    class="button button--primary"
                                    type="submit"
                                >
                                    <span
                                        class="material-symbols-outlined"
                                        aria-hidden="true"
                                    >merge</span>
                                    Unir PDFs
                                </button>

                                <p
                                    class="form-status"
                                    id="pdf-status"
                                    role="status"
                                    aria-live="polite"
                                ></p>

                            </form>