import {Component, DestroyRef, inject, OnInit} from '@angular/core';
import { PageComponent } from '../../components/page/page.component';
import {Button} from 'primeng/button';
import {ConfirmDialog} from 'primeng/confirmdialog';
import {Dialog} from 'primeng/dialog';
import {Divider} from 'primeng/divider';
import {FormBuilder, FormGroup, FormsModule, ReactiveFormsModule, Validators} from '@angular/forms';
import {IconField} from 'primeng/iconfield';
import {InputIcon} from 'primeng/inputicon';
import {InputText} from 'primeng/inputtext';
import {Message} from 'primeng/message';
import {ConfirmationService, MessageService, PrimeTemplate} from 'primeng/api';
import {TableLazyLoadEvent, TableModule, TablePageEvent} from 'primeng/table';
import {Toast} from 'primeng/toast';
import {Tooltip} from 'primeng/tooltip';
import {Article} from '../../../types/models';
import {noWhiteSpaceOnly} from '../../validators/no-whitespace-only';
import {ArticlesService} from '../../services/articles/articles.service';
import {takeUntilDestroyed} from '@angular/core/rxjs-interop';
import {catchError, throwError} from 'rxjs';
import {ListPaginatedItemsResponse, ListPaginatedParams} from '../../../types/requests';
import {Router, RouterLink} from '@angular/router';
import {NgStyle} from '@angular/common';

@Component({
  selector: 'app-admin-articles',
  imports: [
    PageComponent, Button, ConfirmDialog, Dialog, Divider, FormsModule, IconField,
    InputIcon, InputText, Message, PrimeTemplate, ReactiveFormsModule, TableModule,
    Toast, Tooltip, RouterLink, NgStyle
  ],
  providers: [MessageService, ConfirmationService],
  templateUrl: './admin-articles.component.html',
  standalone: true,
  styleUrl: './admin-articles.component.css'
})
export class AdminArticlesComponent implements OnInit {
  sortOrder: number = -1
  sortField: string = 'created_at'
  articles: Article[] = []
  errorMessage: string = ''
  error: boolean = false;
  targetArticle?: Article
  articleForm: FormGroup
  loadingResponse = false
  offset: number = 0
  pageSize: number = 10
  totalCount: number = 0

  #destroyRef = inject(DestroyRef);

  constructor(
    private articlesService: ArticlesService,
    private formBuilder: FormBuilder,
    private messageService: MessageService,
    private confirmationService: ConfirmationService,
    private router: Router
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
    })
  }

  #resetResponseStatusFields(): void {
    this.error = false;
    this.errorMessage = ''
  }

  hasServerError(): boolean {
    return this.error && this.errorMessage != ''
  }

  ngOnInit(): void {
    // this.loadArticles()
  }

  loadArticles(event: TableLazyLoadEvent): void {
    this.#resetResponseStatusFields();

    this.pageSize = event.rows ?? this.pageSize;
    this.offset = event.first ?? 0;
    this.sortField = event.sortField ? event.sortField as string : 'created_at';
    const page = Math.floor(this.offset / this.pageSize) + 1;
    const searchText = event.globalFilter ? event.globalFilter as string : '';

    const params: ListPaginatedParams = {
      page,
      page_size: this.pageSize,
      sort_by: this.sortField,
      sort_order: event.sortOrder === 1 ? 'asc' : 'desc'
    }

    if (searchText.trim() != '') {
      params.search_text = searchText
    }

    this.articlesService.listArticles(params).pipe(
      takeUntilDestroyed(this.#destroyRef),
      catchError(err => {
        this.error = true
        this.errorMessage = 'È avvenuto un errore, contattare l\'amministratore'
        return throwError(() => err)
      })
    ).subscribe((res: ListPaginatedItemsResponse<Article>) => {
      this.articles = res.data.items
      this.totalCount = res.data.total_items
    })
  }

  onModifyArticle(article: Article) {
    this.router.navigate(
      ['/admin/articoli/modifica'],
      { queryParams: { id: article.id } }
    )
  }

  onDeleteArticle(article: Article) {
    this.targetArticle = article
    this.confirmationService.confirm({
      header: 'Cancellazione articolo',
      message: `Vuoi cancellare <i>${this.targetArticle.title}?</i>`,
      accept: this.deleteArticle,
      icon: 'pi pi-question-circle',
    })
  }

  closeConfirmationService(): void {
    this.confirmationService.close()
  }

  deleteArticle(): void {
    this.#resetResponseStatusFields();
    this.loadingResponse = true;

    this.articlesService.deleteArticle(this.targetArticle!.id!)
      .pipe(
        takeUntilDestroyed(this.#destroyRef),
        catchError(err => {
          this.error = true
          this.errorMessage = 'È avvenuto un errore, contattare l\'amministratore'
          this.loadingResponse = false;
          return throwError(() => err)
        })
      ).subscribe(() => {
      this.loadArticles({})
      this.messageService.add({
        severity: 'success',
        summary: 'Articolo cancellato con successo'
      })
      this.loadingResponse = false;
      this.closeConfirmationService()
    })
  }
}
