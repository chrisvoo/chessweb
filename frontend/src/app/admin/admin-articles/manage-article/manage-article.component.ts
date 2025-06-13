import {Component, DestroyRef, inject} from '@angular/core';
import {PageComponent} from '../../../components/page/page.component';
import {FormBuilder, FormGroup, ReactiveFormsModule, Validators} from '@angular/forms';
import {noWhiteSpaceOnly} from '../../../validators/no-whitespace-only';
import {Editor} from 'primeng/editor';
import {InputText} from 'primeng/inputtext';
import {Article, NamedEntity} from '../../../../types/models';
import {CategoriesService} from '../../../services/categories/categories.service';
import {TagsService} from '../../../services/tags/tags.service';
import {ListAllItemsParams, SearchParams} from '../../../../types/requests';
import {map} from 'rxjs';
import {AutoComplete} from 'primeng/autocomplete';
import {Button, ButtonDirective, ButtonLabel} from 'primeng/button';
import {NgStyle} from '@angular/common';

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
    NgStyle
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

  // State for AutoComplete suggestions
  tagSuggestions: NamedEntity[] = [];
  categorySuggestions: NamedEntity[] = [];

  #destroyRef = inject(DestroyRef);

  constructor(
    private formBuilder: FormBuilder,
    private tagsService: TagsService,
    private categoriesService: CategoriesService
  ) {
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
  }

  /**
   * Searches for tags based on user input.
   * @param event The event emitted by p-autoComplete, containing the query.
   */
  searchTags(event: { query: string }) {
    // NOTE: Your ListAllItemsParams type does not include a search parameter.
    // I am assuming your API supports a 'search_text' query param.
    const params: ListAllItemsParams & { name?: string } = {
      all_items: true,
      sort_by: 'name',
      sort_order: 'asc'
    };

    if (event.query) {
      params.name = event.query
    }

    this.tagsService.listTags(params).pipe( //
      map(res => res.data.items) //
    ).subscribe(suggestions => {
      this.tagSuggestions = suggestions; //
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

    this.categoriesService.listCategories(params).pipe( //
      map(res => res.data.items) //
    ).subscribe(suggestions => {
      this.categorySuggestions = suggestions; //
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

    const formValue = this.articleForm.value;

    // New tags will be strings, existing tags will be NamedEntity objects.
    const newTagNames = formValue.tags.filter((tag: any) => typeof tag === 'string');
    const existingTags = formValue.tags.filter((tag: any) => typeof tag === 'object');

    console.log("New tags to create:", newTagNames);
    console.log("Existing tags to link:", existingTags);
    console.log("Selected categories:", formValue.categories);

    // Here you would call your article service to save the data.
    // You might need to create the new tags first, get their IDs, and then
    // save the article with the full list of tag and category IDs.
  }
}
