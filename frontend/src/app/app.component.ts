import { Component } from '@angular/core';
import { RouterOutlet } from '@angular/router';
import {MainComponent} from './main/main.component';
import {CommonModule} from '@angular/common';
import {SidebarComponent} from './sidebar/sidebar.component';

@Component({
  selector: 'app-root',
  imports: [RouterOutlet, MainComponent, SidebarComponent],
  standalone: true,
  templateUrl: './app.component.html',
  styleUrl: './app.component.css'
})
export class AppComponent {
  title = 'frontend';
}
