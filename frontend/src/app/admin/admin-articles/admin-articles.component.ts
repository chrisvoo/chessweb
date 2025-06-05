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
import {TableModule} from 'primeng/table';
import {Toast} from 'primeng/toast';
import {Tooltip} from 'primeng/tooltip';
import {Article} from '../../../types/models';
import {noWhiteSpaceOnly} from '../../validators/no-whitespace-only';
import {ArticlesService} from '../../services/articles/articles.service';
import {takeUntilDestroyed} from '@angular/core/rxjs-interop';
import {catchError, throwError} from 'rxjs';

@Component({
  selector: 'app-admin-articles',
  imports: [
    PageComponent, Button, ConfirmDialog, Dialog, Divider, FormsModule, IconField,
    InputIcon, InputText, Message, PrimeTemplate, ReactiveFormsModule, TableModule,
    Toast, Tooltip
  ],
  providers: [MessageService, ConfirmationService],
  templateUrl: './admin-articles.component.html',
  standalone: true,
  styleUrl: './admin-articles.component.css'
})
export class AdminArticlesComponent implements OnInit {
  sortOrder = -1
  articles: Article[] = []
  errorMessage: string = ''
  error: boolean = false;
  targetArticle?: Article
  articleForm: FormGroup
  loadingResponse = false

  #destroyRef = inject(DestroyRef);

  constructor(
    private articlesService: ArticlesService,
    private formBuilder: FormBuilder,
    private messageService: MessageService,
    private confirmationService: ConfirmationService
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

  ngOnInit(): void {
    this.loadArticles()
  }

  loadArticles(): void {
    this.#resetResponseStatusFields();

    this.articlesService.listArticles({
      page: 1,
      page_size: 10,
      sort_by: 'created_at',
      sort_order: this.sortOrder === 1 ? 'asc' : 'desc',
    }).pipe(
      takeUntilDestroyed(this.#destroyRef),
      catchError(err => {
        this.error = true
        this.errorMessage = 'È avvenuto un errore, contattare l\'amministratore'
        return throwError(() => err)
      })
    ).subscribe((res) => {
      this.articles = res.data.items
    })
  }

  onCreateArticle() {

  }

  onModifyArticle(article: Article) {

  }

  onDeleteArticle(article: Article) {

  }

  closeConfirmationService(): void {
    this.confirmationService.close()
  }

  deleteArticle(): void {

  }


}
