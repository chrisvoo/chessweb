import {Component, DestroyRef, inject, OnInit} from '@angular/core';
import {PageComponent} from '../../../page/page.component';
import {ArticleWithTagsAndCategories, NamedEntity} from '../../../../../types/models';
import {ArticlesService} from '../../../../services/articles/articles.service';
import {ListPaginatedArticles, ListPaginatedItemsResponse} from '../../../../../types/requests';
import {takeUntilDestroyed} from '@angular/core/rxjs-interop';
import {catchError, throwError} from 'rxjs';
import {Paginator, PaginatorState} from 'primeng/paginator';
import {ActivatedRoute, Router, RouterLink} from '@angular/router';
import {ArticleViewerComponent} from '../../../article-viewer/article-viewer.component';
import {Tag} from 'primeng/tag';
import {DatePipe} from '@angular/common';

@Component({
  selector: 'app-news',
  imports: [PageComponent, Paginator, ArticleViewerComponent, Tag, RouterLink, DatePipe],
  templateUrl: './news.component.html',
  standalone: true,
  styleUrl: './news.component.css'
})
export class NewsComponent implements OnInit {
  private router = inject(Router)

  articles: ArticleWithTagsAndCategories[] = []
  offset: number = 0
  pageSize: number = 5
  totalCount: number = 0

  categorySlug?: string
  tagSlug?: string
  searchText?: string
  #destroyRef = inject(DestroyRef)

  constructor(
    private articlesService: ArticlesService,
    private activatedRoute: ActivatedRoute
  ) {
  }

  ngOnInit(): void {
    this.activatedRoute.queryParamMap.subscribe(params => {
      const searchText = params.get('search_text')
      if (searchText !== null && searchText.trim() !== '') {
        this.searchText = searchText
      }

      const endpointParams: ListPaginatedArticles = this.#buildListArticlesParams()
      this.loadArticles(endpointParams)
    })

    this.activatedRoute.paramMap.subscribe(params => {
      const catSlug = params.get('cat_slug')
      if (catSlug !== null) {
        this.categorySlug = catSlug
      }

      const tagSlug = params.get('tag_slug')
      if (tagSlug !== null) {
        this.tagSlug = tagSlug
      }

      const endpointParams: ListPaginatedArticles = this.#buildListArticlesParams()
      this.loadArticles(endpointParams)
    })
  }

  #buildListArticlesParams(): ListPaginatedArticles {
    const endpointParams: ListPaginatedArticles = {
      page: 1,
      page_size: this.pageSize,
      sort_by: 'created_at',
      sort_order: 'desc',
      skip_content: false,
      extra_info: true
    }

    if (this.tagSlug) {
      endpointParams.tag_slug = this.tagSlug
    }

    if (this.categorySlug) {
      endpointParams.cat_slug = this.categorySlug
    }

    if (this.searchText) {
      endpointParams.search_text = this.searchText
    }

    return endpointParams
  }

  loadArticles(params: ListPaginatedArticles): void {
    this.articlesService.listArticles(params).pipe(
      takeUntilDestroyed(this.#destroyRef),
      catchError(err => {
        console.error('È avvenuto un errore, contattare l\'amministratore')
        return throwError(() => err)
      })
    ).subscribe((res: ListPaginatedItemsResponse<ArticleWithTagsAndCategories>) => {
      this.articles = res.data.items
      this.totalCount = res.data.total_items
    })
  }

  onPageChange(event: PaginatorState): void {
    const { first } = event;

    const endpointParams: ListPaginatedArticles = {
      ...this.#buildListArticlesParams(),
      page: Math.floor((first ?? 0) / this.pageSize) + 1
    }

    this.loadArticles(endpointParams)
  }
}
