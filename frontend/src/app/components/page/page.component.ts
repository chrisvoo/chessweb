import { Component, input } from '@angular/core';

@Component({
  selector: 'page',
  imports: [],
  template: `
    <section>
      <header class="main major">
        <h2>{{ title() }}</h2>
      </header>
      <ng-content></ng-content>
    </section>
  `,
  styleUrls: ['./page.component.css'],
  standalone: true,
})
export class PageComponent {
  title = input.required<string>();
}
