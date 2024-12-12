<?php

namespace App\Filament\Resources;

use App\Exports\StudentsExport;
use App\Filament\Resources\StudentResource\Pages;
use App\Filament\Resources\StudentResource\RelationManagers;
use App\Models\Classes;
use App\Models\Section;
use App\Models\Student;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Maatwebsite\Excel\Facades\Excel;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Academic Management';

    public static function getNavigationBadge(): ?string
    {
        return static::$model::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('class_id')
                    ->live()
                    ->relationship(name: 'class', titleAttribute: 'name'),
                Forms\Components\Select::make('section_id')
                    ->label('Section')
                    ->options(function (Forms\Get $get) {
                        $classId = $get('class_id');

                        if ($classId) {
                            return Section::where('class_id', $classId)->pluck('name', 'id')->toArray();
                        }
                    }),
                Forms\Components\TextInput::make('name')
                    ->autofocus()
                    ->required(),
                Forms\Components\TextInput::make('email')
                    ->unique()
                    ->required()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('class.name')
                    ->searchable()
                    ->badge(),
                Tables\Columns\TextColumn::make('section.name')
                    ->searchable()
                    ->badge(),
            ])
            ->filters([
                Tables\Filters\Filter::make('class-section-filter')
                    ->form([
                        Forms\Components\Select::make('class_id')
                            ->label('Filter By Class')
                            ->placeholder('Select a class')
                            ->options(
                                Classes::pluck('name', 'id')->toArray()
                            ),

                        Forms\Components\Select::make('section_id')
                            ->label('Filter By Section')
                            ->placeholder('Select a section')
                            ->options(function (Forms\Get $get) {
                                $classId = $get('class_id');

                                if ($classId) {
                                    return Section::where('class_id', $classId)->pluck('name', 'id')->toArray();
                                }
                            })
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when($data['class_id'], function ($query) use ($data) {
                            return $query->where('class_id', $data['class_id']);
                        })->when($data['section_id'], function ($query) use ($data) {
                            return $query->where('section_id', $data['section_id']);
                        });
                    })
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('export')
                        ->label('Export to Excel')
                        ->icon('heroicon-o-document-arrow-down')
                        ->action(function (Collection $records) {
                            return Excel::download(new StudentsExport($records), 'students.xlsx');
                        })
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudents::route('/'),
            'create' => Pages\CreateStudent::route('/create'),
            'edit' => Pages\EditStudent::route('/{record}/edit'),
        ];
    }
}
