import {Component, inject} from '@angular/core';
import {FormsModule} from '@angular/forms';
import {IconField} from 'primeng/iconfield';
import {InputIcon} from 'primeng/inputicon';
import {Router} from '@angular/router';
import {NgStyle} from '@angular/common';

@Component({
  selector: 'app-searchbox',
  imports: [
    FormsModule,
    IconField,
    InputIcon,
    NgStyle
  ],
  standalone: true,
  templateUrl: './searchbox.component.html',
  styleUrl: './searchbox.component.css'
})
export class SearchboxComponent {
  router = inject(Router)

  query = ''

  search() {
    if (this.query.trim().length >= 3) {
      void this.router.navigate(
        ['/notizie'],
        {
          queryParams: {
            search_text: this.query
          }
        }
      )
    }
  }
}
