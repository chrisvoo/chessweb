import {AfterViewInit, Component, DestroyRef, inject, OnInit, ViewChild} from '@angular/core';
import {PageComponent} from '../../../components/page/page.component';
import {FormBuilder, FormGroup, ReactiveFormsModule, Validators} from '@angular/forms';
import {noWhiteSpaceOnly} from '../../../validators/no-whitespace-only';
import {Editor, EditorModule} from 'primeng/editor';
import {InputText} from 'primeng/inputtext';
import {Article, NamedEntity, ArticleWithTagsAndCategories} from '../../../../types/models';
import {CategoriesService} from '../../../services/categories/categories.service';
import {TagsService} from '../../../services/tags/tags.service';
import {ListAllItemsParams, ViewArticleResponse} from '../../../../types/requests';
import {catchError, map, throwError} from 'rxjs';
import {AutoComplete} from 'primeng/autocomplete';
import {Button, ButtonDirective, ButtonLabel} from 'primeng/button';
import {NgStyle} from '@angular/common';
import Quill from 'quill';
import {Dialog} from 'primeng/dialog';
import {Message} from 'primeng/message';
import {FileUploadModule} from 'primeng/fileupload';
import {ArticlesService} from '../../../services/articles/articles.service';
import {takeUntilDestroyed} from '@angular/core/rxjs-interop';
import {ActivatedRoute, Router} from '@angular/router';
import {CustomImageBlot} from './custom-image-blot';

@Component({
  selector: 'app-manage-article',
  imports: [
    PageComponent,
    ReactiveFormsModule,
    Editor,
    InputText,
    AutoComplete,
    ButtonDirective,
    ButtonLabel,
    Button,
    NgStyle,
    Dialog,
    Message,
    FileUploadModule
  ],
  templateUrl: './manage-article.component.html',
  styleUrl: './manage-article.component.css',
  standalone: true
})
export class ManageArticleComponent {
  articleForm: FormGroup
  loadingResponse = false
  errorMessage: string = ''
  error: boolean = false;
  targetArticle?: Article

  #destroyRef = inject(DestroyRef);

  // State for AutoComplete suggestions
  tagSuggestions: NamedEntity[] = [];
  categorySuggestions: NamedEntity[] = [];

  @ViewChild('editor') editor!: Editor;
  quill?: Quill // the Quill editor instance

  uploadImageForm: FormGroup
  isDialogVisible = false
  maxWidth = 350
  maxHeight = 350
  imgFile?: File

  constructor(
    private formBuilder: FormBuilder,
    private tagsService: TagsService,
    private articleService: ArticlesService,
    private categoriesService: CategoriesService,
    private router: Router,
    private activatedRoute: ActivatedRoute,
  ) {
    Quill.register(CustomImageBlot, true);

    // main edit form
    this.articleForm = this.formBuilder.group({
      title: ['', Validators.compose([
        Validators.required,
        noWhiteSpaceOnly()
      ])],
      content: ['', Validators.compose([
        Validators.required,
        noWhiteSpaceOnly()
      ])],
      tags: [[]],
      categories: [[]]
    })

    // upload image form
    this.uploadImageForm = this.formBuilder.group({
      // width: ['', Validators.compose([
      //   Validators.pattern("^[0-9]*$"),
      //   Validators.min(25),
      //   Validators.max(2000),
      // ])],
      // height: ['', Validators.compose([
      //   Validators.pattern("^[0-9]*$"),
      //   Validators.min(25),
      //   Validators.max(2000),
      // ])],
    })
  }

  onEditorInit(event: any) {
    // The event object contains the quill instance
    this.quill = event.editor;
    if (!this.quill) {
      console.error('Quill cannot be found!')
      return
    }

    // Override the default image handler
    const module = this.quill.getModule('toolbar') as any
    module.addHandler('image', () => {
      this.customImageHandler();
    });

    const resolvedData = this.activatedRoute.snapshot.data;
    if (resolvedData && resolvedData['article']) {
      const articleResponse = resolvedData['article'] as ViewArticleResponse
      if (articleResponse.statusCode === 200) {
        const { title, content, categories, tags } = articleResponse.data
        this.articleForm.controls['title'].setValue(title)
        this.articleForm.controls['content'].setValue(content)
        this.articleForm.controls['tags'].setValue(tags ?? [])
        this.articleForm.controls['categories'].setValue(categories ?? [])
      }
    }
  }

  insertImageWithResize() {
    const theFinalMaxWidth = this.uploadImageForm.get('width')?.value !== ''
      ? this.uploadImageForm.get('width')?.value : this.maxWidth
    const theFinalMaHeight = this.uploadImageForm.get('height')?.value !== ''
      ? this.uploadImageForm.get('height')?.value : this.maxHeight

    if (this.imgFile) {
      const reader = new FileReader();
      reader.onload = (e: any) => {
        const img = document.createElement('img');
        img.onload = () => {
          const canvas = document.createElement('canvas');
          let width = img.width;
          let height = img.height;

          // Calculate new dimensions while maintaining aspect ratio
          if (width > height) {
            if (width > theFinalMaxWidth) {
              height *= theFinalMaxWidth / width;
              width = theFinalMaxWidth;
            }
          } else {
            if (height > theFinalMaHeight) {
              width *= theFinalMaHeight / height;
              height = theFinalMaHeight;
            }
          }

          canvas.width = width;
          canvas.height = height;

          const ctx = canvas.getContext('2d');
          if (ctx) {
            ctx.drawImage(img, 0, 0, width, height);

            // Get the resized image as a base64 string
            const base64String = canvas.toDataURL(this.imgFile!.type);

            // Insert the image into the editor
            const range = this.quill!.getSelection(true);
            this.quill!.insertEmbed(range.index, 'image', base64String);

            // Apply the CSS classes
            // This is a bit of a workaround as Quill doesn't directly support adding classes to the image embed.
            // We find the image we just inserted and set its class.
            setTimeout(() => {
              const editorElement = this.quill!.root as HTMLElement;
              const images = editorElement.getElementsByTagName('img');
              for (let i = 0; i < images.length; i++) {
                if (images[i].src === base64String) {
                  images[i].className = 'responsive';
                  break;
                }
              }
            }, 100); // A small delay to ensure the image is in the DOM
          }
        }
        img.src = e.target.result;
      }
      reader.readAsDataURL(this.imgFile);
    }

    this.isDialogVisible = false;
  }

  insertImage() {
    if (this.imgFile) {
      const reader = new FileReader();
      reader.onload = (e: any) => {
        // The full-resolution base64 string
        const base64String = e.target.result;
        const range = this.quill!.getSelection(true);
        // Insert the full-size image into the editor
        this.quill!.insertEmbed(range.index, 'image', base64String);
        // Move cursor past the image
        this.quill!.setSelection(range.index + 1);
        // Find the image we just inserted and apply styles and a class
        // This is the key change: we apply style for editor preview
        // and a class for identification in the viewer component.
        setTimeout(() => {
          const editorElement = this.quill!.root as HTMLElement;
          const img = editorElement.querySelector(`img[src="${base64String}"]`);
          if (img) {
            img.setAttribute('style', 'max-width: 400px; height: auto; cursor: pointer;');
            img.classList.add('article-image-preview');
          }
        }, 100);
      }
      reader.readAsDataURL(this.imgFile);
    }

    this.isDialogVisible = false;
  }

  onUpload($event: any) {
    const files: FileList = $event.target.files
    if (files != null && files.length) {
      this.imgFile = files[0];
    }
  }

  /**
   * This just starts the custom image handler process by showing the dialog
   * for uploading an image
   */
  customImageHandler() {
    this.isDialogVisible = true;
    this.imgFile = undefined
  }

  /**
   * Searches for tags based on user input.
   * @param event The event emitted by p-autoComplete, containing the query.
   */
  searchTags(event: { query: string }) {
    const params: ListAllItemsParams & { name?: string } = {
      all_items: true,
      sort_by: 'name',
      sort_order: 'asc'
    };

    if (event.query) {
      params.name = event.query
    }

    const selectedTags: NamedEntity[] = this.articleForm.get('tags')?.value || [];

    this.tagsService.listTags(params).pipe(
      map(res => res.data.items)
    ).subscribe(suggestions => {
      // Filter out suggestions that are already selected by comparing their IDs
      this.tagSuggestions = suggestions.filter(suggestion =>
        !selectedTags.some(selected => selected.id === suggestion.id)
      );
    });
  }

  /**
   * Searches for categories based on user input.
   * @param event The event emitted by p-autoComplete, containing the query.
   */
  searchCategories(event: { query: string }) {
    const params: ListAllItemsParams & { name?: string } = {
      all_items: true,
      sort_by: 'name',
      sort_order: 'asc',
    }

    if (event.query) {
      params.name = event.query
    }

    const selectedCategories: NamedEntity[] = this.articleForm.get('categories')?.value || [];

    this.categoriesService.listCategories(params).pipe(
      map(res => res.data.items)
    ).subscribe(suggestions => {
      // Filter out suggestions that are already selected by comparing their IDs
      this.categorySuggestions = suggestions.filter(suggestion =>
        !selectedCategories.some(selected => selected.id === suggestion.id)
      );
    });
  }

  /**
   * Placeholder for a method to save the article.
   * This shows how to handle newly created tags.
   */
  saveArticle() {
    if (this.articleForm.invalid) {
      return;
    }

    const { tags, categories, title, content } = this.articleForm.value;
    const newArticle: ArticleWithTagsAndCategories = {
      tags,
      categories,
      title,
      content,
    }
    console.log("Submit:", { tags, categories, title, content });

    this.articleService.createArticle(newArticle)
      .pipe(
        takeUntilDestroyed(this.#destroyRef),
        catchError(err => {
          this.error = true
          this.errorMessage = 'È avvenuto un errore, contattare l\'amministratore'
          return throwError(() => err)
        })
      ).subscribe(res => {
        this.router.navigate(['/admin/articoli'])
      })
  }
}
