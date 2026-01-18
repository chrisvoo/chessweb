import {Component, DestroyRef, inject, OnInit, ViewChild} from '@angular/core';
import {PageComponent} from '../../../components/page/page.component';
import {FormBuilder, FormGroup, ReactiveFormsModule, Validators} from '@angular/forms';
import {noWhiteSpaceOnly} from '../../../validators/no-whitespace-only';
import {Editor} from 'primeng/editor';
import {InputText} from 'primeng/inputtext';
import {Article, NamedEntity, ArticleWithTagsAndCategories} from '../../../../types/models';
import {CategoriesService} from '../../../services/categories/categories.service';
import {TagsService} from '../../../services/tags/tags.service';
import {ListAllItemsParams, ManagedEntityResponse, ViewArticleResponse} from '../../../../types/requests';
import {catchError, map, Observable, throwError} from 'rxjs';
import {AutoComplete} from 'primeng/autocomplete';
import {Button} from 'primeng/button';
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
    Button,
    Dialog,
    Message,
    FileUploadModule
  ],
  templateUrl: './manage-article.component.html',
  styleUrl: './manage-article.component.css',
  standalone: true
})
export class ManageArticleComponent implements OnInit {
  articleForm: FormGroup
  loadingResponse = false
  errorMessage: string = ''
  error: boolean = false;
  targetArticle?: Article
  // Track the ID to determine if we are editing
  articleId: number | null = null;

  #destroyRef = inject(DestroyRef);

  // State for AutoComplete suggestions
  tagSuggestions: NamedEntity[] = [];
  categorySuggestions: NamedEntity[] = [];

  @ViewChild('editor') editor!: Editor;
  quill?: Quill // the Quill editor instance

  uploadImageForm: FormGroup
  isDialogVisible = false
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
    this.uploadImageForm = this.formBuilder.group({})
  }

  ngOnInit(): void {
    // Subscribe to data to handle route changes or reuse
    this.activatedRoute.data
      .pipe(takeUntilDestroyed(this.#destroyRef))
      .subscribe(data => {
        const articleResponse = data['article'] as ViewArticleResponse;

        // Check if response and data exist
        if (articleResponse && articleResponse.data) {
          const { id, title, content, categories, tags } = articleResponse.data;

          this.articleId = id ?? null;

          // Populate form immediately
          this.articleForm.patchValue({
            title: title,
            content: content,
            tags: tags ?? [],
            categories: categories ?? []
          });
        }
      });
  }

  // Helper to determine UI state
  get isEditMode(): boolean {
    return !!this.articleId;
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
        this.quill!.formatText(range.index, 1, {
          style: 'max-width: 400px; height: auto; cursor: pointer;',
          class: 'article-image-preview'
        });

        setTimeout(() => {
          // force update content of the form
          const htmlContent = this.quill!.root.innerHTML;
          this.articleForm.controls['content'].setValue(htmlContent);
          this.articleForm.controls['content'].markAsDirty();
          // Move cursor past the image
          this.quill!.setSelection(range.index + 1);
        }, 0);
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
    const articlePayload: ArticleWithTagsAndCategories = {
      tags,
      categories,
      title,
      content
    }
    console.log("Submit:", { tags, categories, title, content });

    let request$: Observable<ManagedEntityResponse>;

    if (this.isEditMode && this.articleId) {
      const updatePayload: ArticleWithTagsAndCategories = {
        ...articlePayload,
        id: this.articleId
      };

      request$ = this.articleService.updateArticle(updatePayload);
    } else {
      request$ = this.articleService.createArticle(articlePayload);
    }

    this.loadingResponse = true;

    request$.pipe(
      takeUntilDestroyed(this.#destroyRef),
      catchError(err => {
        this.loadingResponse = false;
        this.error = true;
        this.errorMessage = 'È avvenuto un errore, contattare l\'amministratore';
        return throwError(() => err);
      })
    ).subscribe(res => {
      this.loadingResponse = false;
      this.router.navigate(['/admin/articoli']);
    });
  }
}
